<?php

namespace App\Support\Dashboard;

use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\Region;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentCheckin;
use App\Models\StudentSalePayment;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardMetrics
{
    public const PRODUCTS = [
        'plan_basic' => 'Planos básicos',
        'plan_classes' => 'Planos de aulas',
        'plan_mixed' => 'Planos mistos',
        'day_pass' => 'Diárias',
        'class' => 'Aulas avulsas',
        'other' => 'Produtos e serviços',
    ];

    public const METHODS = [
        'cash' => 'Dinheiro',
        'pix' => 'Pix',
        'debit_card' => 'Cartão de débito',
        'credit_card' => 'Cartão de crédito',
        'transfer' => 'Transferência',
        'mixed' => 'Pagamento misto',
        'manual' => 'Outro / manual',
        'undefined' => 'A definir',
    ];

    public const PRESETS = [
        '7d' => 'Últimos 7 dias',
        '30d' => 'Últimos 30 dias',
        '90d' => 'Últimos 90 dias',
        'month' => 'Este mês',
        'custom' => 'Personalizado',
    ];

    private string $level;

    private ?int $networkId = null;

    private ?int $regionId = null;

    private ?int $unitId = null;

    private Carbon $from;

    private Carbon $to;

    private ?string $status = null;

    private ?string $product = null;

    private ?string $method = null;

    private ?string $segment = null;

    private string $preset = '30d';

    /** @var Collection<int, Unit> */
    private Collection $allowedUnits;

    /** @var array<int, int> */
    private array $unitIds = [];

    /** @var Collection<int, int> */
    private Collection $activeStudentIds;

    public function __construct(
        private readonly Request $request,
        private readonly TenantContext $tenant,
    ) {
        $this->level = $tenant->level();
        $this->allowedUnits = $tenant->unitsQuery()->get(['id', 'name', 'network_id', 'region_id']);
        $this->resolvePeriod();
        $this->resolveScope();
        $this->resolveDimensionFilters();
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $paid = $this->paidCharges();
        $pending = $this->pendingCharges();

        $allocation = $this->allocateSalePayments($paid);

        $paidRows = $this->normalizePaidRows($paid, $allocation);
        $pendingRows = $this->normalizePendingRows($pending);

        $received = round($paidRows->sum('amount'), 2);
        $pendingFuture = round($pendingRows->where('bucket', 'future')->sum('amount'), 2);
        $overdue = round($pendingRows->where('bucket', 'overdue')->sum('amount'), 2);

        $previous = $this->previousPeriodTotals();

        $buckets = $this->buildBuckets();

        return [
            'filters' => $this->filters(),
            'options' => $this->options(),
            'scope' => $this->scopeSummary(),
            'presets' => $this->presetLinks(),
            'period' => [
                'from' => $this->from->toDateString(),
                'to' => $this->to->toDateString(),
                'label' => $this->from->format('d/m/Y').' a '.$this->to->format('d/m/Y'),
                'days' => (int) $this->from->diffInDays($this->to) + 1,
                'previous_label' => $previous['from']->format('d/m/Y').' a '.$previous['to']->format('d/m/Y'),
            ],
            'revenue' => [
                'total_expected' => round($received + $pendingFuture + $overdue, 2),
                'received' => $received,
                'pending_future' => $pendingFuture,
                'overdue' => $overdue,
                'comparison' => [
                    'received' => $this->percentChange($received, $previous['received']),
                    'pending_future' => $this->percentChange($pendingFuture, $previous['pending_future']),
                    'overdue' => $this->percentChange($overdue, $previous['overdue']),
                    'total_expected' => $this->percentChange(
                        $received + $pendingFuture + $overdue,
                        $previous['received'] + $previous['pending_future'] + $previous['overdue']
                    ),
                ],
                'previous' => [
                    'received' => $previous['received'],
                    'pending_future' => $previous['pending_future'],
                    'overdue' => $previous['overdue'],
                ],
                'products' => [
                    'received' => $this->productBreakdown($paidRows),
                    'pending_future' => $this->productBreakdown($pendingRows->where('bucket', 'future')),
                    'overdue' => $this->productBreakdown($pendingRows->where('bucket', 'overdue')),
                ],
                'methods' => [
                    'received' => $this->methodBreakdown($paidRows),
                    'pending' => $this->methodBreakdown($pendingRows),
                ],
                'series' => $this->revenueSeries($paidRows, $pendingRows, $buckets),
            ],
            'students' => $this->studentMetrics($buckets),
            'transactions' => $this->transactions($paidRows, $pendingRows),
            'has_any_data' => $paidRows->isNotEmpty() || $pendingRows->isNotEmpty(),
        ];
    }

    private function resolvePeriod(): void
    {
        $to = $this->request->filled('to')
            ? Carbon::parse((string) $this->request->input('to'))->startOfDay()
            : today();

        $preset = (string) $this->request->input('preset', '');
        $hasExplicitRange = $this->request->filled('from') && $this->request->filled('to');

        if ($hasExplicitRange) {
            $from = Carbon::parse((string) $this->request->input('from'))->startOfDay();
        } else {
            $from = match ($preset) {
                '7d' => $to->copy()->subDays(6),
                '90d' => $to->copy()->subDays(89),
                'month' => $to->copy()->startOfMonth(),
                '30d' => $to->copy()->subDays(29),
                default => $to->copy()->subDays(29),
            };
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        if ($from->lt($to->copy()->subYears(2))) {
            $from = $to->copy()->subYears(2);
        }

        $this->from = $from;
        $this->to = $to;
        $this->preset = $this->detectPreset($preset);
    }

    private function detectPreset(string $requested): string
    {
        $candidates = [
            '7d' => 6,
            '30d' => 29,
            '90d' => 89,
        ];

        foreach ($candidates as $key => $days) {
            if ($this->from->isSameDay($this->to->copy()->subDays($days))) {
                return $key;
            }
        }

        if ($this->from->isSameDay($this->to->copy()->startOfMonth())
            && array_key_exists($requested, self::PRESETS)
            && $requested === 'month') {
            return 'month';
        }

        return 'custom';
    }

    private function resolveScope(): void
    {
        $units = $this->allowedUnits;

        if ($this->level === 'platform') {
            $networkId = (int) $this->request->input('network_id', 0);

            if ($networkId > 0 && $units->contains(fn ($unit) => (int) $unit->network_id === $networkId)) {
                $this->networkId = $networkId;
                $units = $units->where('network_id', $networkId);
            }
        } else {
            $this->networkId = $this->tenant->networkId();
        }

        if (in_array($this->level, ['platform', 'network'], true)) {
            $regionId = (int) $this->request->input('region_id', 0);

            if ($regionId > 0 && $units->contains(fn ($unit) => (int) $unit->region_id === $regionId)) {
                $this->regionId = $regionId;
                $units = $units->where('region_id', $regionId);
            }
        } elseif ($this->level === 'region') {
            $this->regionId = $this->tenant->regionId();
        }

        if (in_array($this->level, ['platform', 'network', 'region'], true)) {
            $unitId = (int) $this->request->input('unit_id', 0);

            if ($unitId > 0 && $units->contains(fn ($unit) => (int) $unit->id === $unitId)) {
                $this->unitId = $unitId;
                $units = $units->where('id', $unitId);
            }
        }

        if ($this->level === 'unit') {
            $this->unitId = $this->tenant->unitId();
            $units = $units->where('id', $this->unitId);
        }

        $this->allowedUnits = $units->values();
        $this->unitIds = $this->allowedUnits->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function resolveDimensionFilters(): void
    {
        $status = (string) $this->request->input('status', '');

        if (in_array($status, ['paid', 'pending_future', 'overdue'], true)) {
            $this->status = $status;
        }

        $product = (string) $this->request->input('product', '');

        if (array_key_exists($product, self::PRODUCTS)) {
            $this->product = $product;
        }

        $method = (string) $this->request->input('method', '');

        if (array_key_exists($method, self::METHODS)) {
            $this->method = $method;
        }

        $segment = (string) $this->request->input('segment', '');

        if (in_array($segment, ['recurring', 'new', 'trial', 'converted', 'without_plan'], true)) {
            $this->segment = $segment;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return [
            'network_id' => $this->networkId,
            'region_id' => $this->regionId,
            'unit_id' => $this->unitId,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'status' => $this->status,
            'product' => $this->product,
            'method' => $this->method,
            'segment' => $this->segment,
            'preset' => $this->preset,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        $networks = $this->level === 'platform'
            ? Network::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $regionIds = $this->allowedUnits->pluck('region_id')->filter()->unique();

        $regions = Region::query()
            ->whereIn('id', $regionIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'networks' => $networks,
            'regions' => $regions,
            'units' => $this->allowedUnits->sortBy('name')->values(),
            'network_filter' => $this->level === 'platform',
            'region_filter' => in_array($this->level, ['platform', 'network'], true),
            'unit_filter' => in_array($this->level, ['platform', 'network', 'region'], true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scopeSummary(): array
    {
        $network = $this->networkId ? Network::find($this->networkId, ['id', 'name']) : null;
        $region = $this->regionId ? Region::find($this->regionId, ['id', 'name']) : null;
        $unit = $this->unitId ? $this->allowedUnits->firstWhere('id', $this->unitId) : null;

        return [
            'level' => $this->level,
            'level_label' => match ($this->level) {
                'platform' => 'Proprietário / plataforma',
                'network' => 'Rede',
                'region' => 'Região',
                default => 'Unidade',
            },
            'network_name' => $network?->name,
            'region_name' => $region?->name,
            'unit_name' => $unit?->name,
            'unit_count' => $this->allowedUnits->count(),
        ];
    }

    /**
     * @return array<string, array{from: string, to: string}>
     */
    private function presetLinks(): array
    {
        $links = [];

        foreach (['7d' => 6, '30d' => 29, '90d' => 89] as $key => $days) {
            $links[$key] = [
                'from' => $this->to->copy()->subDays($days)->toDateString(),
                'to' => $this->to->toDateString(),
            ];
        }

        $links['month'] = [
            'from' => $this->to->copy()->startOfMonth()->toDateString(),
            'to' => $this->to->toDateString(),
        ];

        return $links;
    }

    /**
     * @return Collection<int, StudentCharge>
     */
    private function paidCharges(): Collection
    {
        if ($this->unitIds === []) {
            return collect();
        }

        return StudentCharge::query()
            ->whereIn('unit_id', $this->unitIds)
            ->where('status', StudentCharge::STATUS_PAID)
            ->whereBetween('paid_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->with(['subscription:id,plan_type', 'student:id,name', 'unit:id,name'])
            ->orderBy('paid_at')
            ->get([
                'id',
                'unit_id',
                'student_id',
                'student_sale_id',
                'type',
                'description',
                'amount',
                'payment_method',
                'paid_at',
                'due_date',
                'reference_type',
                'reference_id',
                'metadata',
            ]);
    }

    /**
     * @return Collection<int, StudentCharge>
     */
    private function pendingCharges(): Collection
    {
        if ($this->unitIds === []) {
            return collect();
        }

        return StudentCharge::query()
            ->whereIn('unit_id', $this->unitIds)
            ->where('status', StudentCharge::STATUS_PENDING)
            ->with(['subscription:id,plan_type', 'student:id,name', 'unit:id,name'])
            ->orderBy('due_date')
            ->get([
                'id',
                'unit_id',
                'student_id',
                'student_sale_id',
                'type',
                'description',
                'amount',
                'payment_method',
                'paid_at',
                'due_date',
                'reference_type',
                'reference_id',
                'metadata',
            ]);
    }

    /**
     * Split each sale payment across its charges so the payment-method view
     * reconciles with the charge totals even when a sale is split.
     *
     * @param  Collection<int, StudentCharge>  $paid
     * @return array<int, array<string, float>>
     */
    private function allocateSalePayments(Collection $paid): array
    {
        $saleIds = $paid->pluck('student_sale_id')->filter()->unique()->values();

        if ($saleIds->isEmpty()) {
            return [];
        }

        $payments = StudentSalePayment::query()
            ->whereIn('student_sale_id', $saleIds)
            ->orderBy('id')
            ->get(['id', 'student_sale_id', 'method', 'amount'])
            ->groupBy('student_sale_id');

        $charges = StudentCharge::query()
            ->whereIn('student_sale_id', $saleIds)
            ->where('status', StudentCharge::STATUS_PAID)
            ->orderBy('id')
            ->get(['id', 'student_sale_id', 'amount'])
            ->groupBy('student_sale_id');

        $allocation = [];

        foreach ($charges as $saleId => $saleCharges) {
            $buckets = $payments->get($saleId, collect())
                ->map(fn ($payment) => [
                    'method' => $payment->method,
                    'amount' => (float) $payment->amount,
                ])
                ->values()
                ->all();

            foreach ($saleCharges as $charge) {
                $remaining = (float) $charge->amount;

                foreach ($buckets as &$bucket) {
                    if ($remaining <= 0) {
                        break;
                    }

                    if ($bucket['amount'] <= 0) {
                        continue;
                    }

                    $take = min($remaining, $bucket['amount']);

                    $allocation[(int) $charge->id][$bucket['method']] = round(
                        ($allocation[(int) $charge->id][$bucket['method']] ?? 0) + $take,
                        2
                    );

                    $bucket['amount'] = round($bucket['amount'] - $take, 2);
                    $remaining = round($remaining - $take, 2);
                }
                unset($bucket);

                if ($remaining > 0) {
                    $allocation[(int) $charge->id]['manual'] = round(
                        ($allocation[(int) $charge->id]['manual'] ?? 0) + $remaining,
                        2
                    );
                }
            }
        }

        return $allocation;
    }

    /**
     * @param  Collection<int, StudentCharge>  $paid
     * @param  array<int, array<string, float>>  $allocation
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizePaidRows(Collection $paid, array $allocation): Collection
    {
        return $paid
            ->map(function (StudentCharge $charge) use ($allocation) {
                $methods = $allocation[(int) $charge->id] ?? null;

                if ($methods === null) {
                    $method = $charge->payment_method ?: 'manual';
                    $method = array_key_exists($method, self::METHODS) ? $method : 'manual';
                    $methods = [$method => (float) $charge->amount];
                }

                return [
                    'charge' => $charge,
                    'product' => $this->productKey($charge),
                    'methods' => $methods,
                    'amount' => 0.0,
                    'bucket' => 'paid',
                ];
            })
            ->map(function (array $row) {
                $row['amount'] = $this->methodFilteredAmount($row['methods']);

                return $row;
            })
            ->when($this->product, fn (Collection $rows) => $rows->where('product', $this->product))
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->values();
    }

    /**
     * @param  Collection<int, StudentCharge>  $pending
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizePendingRows(Collection $pending): Collection
    {
        return $pending
            ->map(function (StudentCharge $charge) {
                $method = $charge->payment_method ?: 'undefined';
                $method = array_key_exists($method, self::METHODS) ? $method : 'undefined';
                $due = $charge->due_date?->copy()->startOfDay() ?? today();
                $bucket = $due->lt(today()) ? 'overdue' : 'future';

                return [
                    'charge' => $charge,
                    'product' => $this->productKey($charge),
                    'methods' => [$method => (float) $charge->amount],
                    'amount' => $this->methodFilteredAmount([$method => (float) $charge->amount]),
                    'bucket' => $bucket,
                ];
            })
            ->when($this->product, fn (Collection $rows) => $rows->where('product', $this->product))
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->values();
    }

    /**
     * @param  array<string, float>  $methods
     */
    private function methodFilteredAmount(array $methods): float
    {
        if ($this->method === null) {
            return round(array_sum($methods), 2);
        }

        return round((float) ($methods[$this->method] ?? 0), 2);
    }

    private function productKey(StudentCharge $charge): string
    {
        if ($charge->type === StudentCharge::TYPE_PLAN) {
            $planType = $charge->subscription?->plan_type ?? data_get($charge->metadata, 'plan_type');

            return match ($planType) {
                MembershipPlan::TYPE_CLASSES => 'plan_classes',
                MembershipPlan::TYPE_MIXED => 'plan_mixed',
                default => 'plan_basic',
            };
        }

        return match ($charge->type) {
            StudentCharge::TYPE_DAY_PASS => 'day_pass',
            StudentCharge::TYPE_CLASS => 'class',
            default => 'other',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function productBreakdown(Collection $rows): array
    {
        return collect(self::PRODUCTS)
            ->map(function (string $label, string $key) use ($rows) {
                $subset = $rows->where('product', $key);

                return [
                    'key' => $key,
                    'label' => $label,
                    'amount' => round((float) $subset->sum('amount'), 2),
                    'count' => $subset->count(),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function methodBreakdown(Collection $rows): array
    {
        $totals = [];

        foreach ($rows as $row) {
            foreach ($row['methods'] as $method => $amount) {
                if ($this->method !== null && $method !== $this->method) {
                    continue;
                }

                $totals[$method] = round(($totals[$method] ?? 0) + (float) $amount, 2);
            }
        }

        return collect($totals)
            ->filter(fn (float $amount) => $amount > 0)
            ->map(fn (float $amount, string $method) => [
                'key' => $method,
                'label' => self::METHODS[$method] ?? $method,
                'amount' => round($amount, 2),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $paidRows
     * @param  Collection<int, array<string, mixed>>  $pendingRows
     * @param  array<int, array<string, mixed>>  $buckets
     * @return array<string, mixed>
     */
    private function revenueSeries(Collection $paidRows, Collection $pendingRows, array $buckets): array
    {
        $labels = [];
        $received = [];
        $pending = [];
        $overdue = [];
        $ranges = [];

        foreach ($buckets as $bucket) {
            $start = $bucket['from']->copy()->startOfDay();
            $end = $bucket['to']->copy()->endOfDay();

            $bucketReceived = $paidRows
                ->filter(fn (array $row) => $row['charge']->paid_at && $row['charge']->paid_at->betweenIncluded($start, $end))
                ->sum('amount');

            $bucketPending = $pendingRows
                ->filter(fn (array $row) => $row['bucket'] === 'future'
                    && $row['charge']->due_date
                    && $row['charge']->due_date->copy()->startOfDay()->betweenIncluded($start, $end))
                ->sum('amount');

            $bucketOverdue = $pendingRows
                ->filter(fn (array $row) => $row['bucket'] === 'overdue'
                    && $row['charge']->due_date
                    && $row['charge']->due_date->copy()->startOfDay()->betweenIncluded($start, $end))
                ->sum('amount');

            $labels[] = $bucket['label'];
            $received[] = round((float) $bucketReceived, 2);
            $pending[] = round((float) $bucketPending, 2);
            $overdue[] = round((float) $bucketOverdue, 2);
            $ranges[] = [
                'from' => $bucket['from']->toDateString(),
                'to' => $bucket['to']->toDateString(),
            ];
        }

        return [
            'labels' => $labels,
            'received' => $received,
            'pending' => $pending,
            'overdue' => $overdue,
            'ranges' => $ranges,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function previousPeriodTotals(): array
    {
        $days = (int) $this->from->diffInDays($this->to) + 1;
        $prevTo = $this->from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);

        if ($this->unitIds === []) {
            return [
                'from' => $prevFrom,
                'to' => $prevTo,
                'received' => 0.0,
                'pending_future' => 0.0,
                'overdue' => 0.0,
            ];
        }

        $paid = StudentCharge::query()
            ->whereIn('unit_id', $this->unitIds)
            ->where('status', StudentCharge::STATUS_PAID)
            ->whereBetween('paid_at', [$prevFrom->copy()->startOfDay(), $prevTo->copy()->endOfDay()])
            ->with(['subscription:id,plan_type'])
            ->get(['id', 'unit_id', 'student_sale_id', 'type', 'amount', 'payment_method', 'paid_at', 'reference_type', 'reference_id', 'metadata']);

        $prevAllocation = $this->allocateSalePayments($paid);
        $paidRows = $this->normalizePaidRows($paid, $prevAllocation);

        $pending = StudentCharge::query()
            ->whereIn('unit_id', $this->unitIds)
            ->where('status', StudentCharge::STATUS_PENDING)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $prevTo->toDateString())
            ->with(['subscription:id,plan_type'])
            ->get(['id', 'unit_id', 'student_sale_id', 'type', 'amount', 'payment_method', 'due_date', 'reference_type', 'reference_id', 'metadata']);

        $pendingRows = $this->normalizePendingRows($pending);

        return [
            'from' => $prevFrom,
            'to' => $prevTo,
            'received' => round($paidRows->sum('amount'), 2),
            'pending_future' => round($pendingRows->where('bucket', 'future')->sum('amount'), 2),
            'overdue' => round($pendingRows->where('bucket', 'overdue')->sum('amount'), 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $buckets
     * @return array<string, mixed>
     */
    private function studentMetrics(array $buckets): array
    {
        $studentIds = StudentUnit::query()
            ->whereIn('unit_id', $this->unitIds)
            ->where('status', 'active')
            ->distinct()
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id);

        $this->activeStudentIds = Student::query()
            ->whereIn('id', $studentIds)
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $recurring = $this->recurringStudentIds($this->to);
        $recurringPrevious = $this->recurringStudentIds($this->from->copy()->subDay());

        $newIds = Student::query()
            ->whereIn('id', $this->activeStudentIds)
            ->whereBetween('created_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $previousDays = (int) $this->from->diffInDays($this->to) + 1;
        $prevTo = $this->from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($previousDays - 1);

        $newPrevious = Student::query()
            ->whereIn('id', $this->activeStudentIds)
            ->whereBetween('created_at', [$prevFrom->copy()->startOfDay(), $prevTo->copy()->endOfDay()])
            ->count();

        $trialRows = StudentCheckin::query()
            ->whereIn('unit_id', $this->unitIds)
            ->where('type', StudentCheckin::TYPE_TRIAL)
            ->whereIn('student_id', $this->activeStudentIds)
            ->whereBetween('checked_in_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->orderBy('checked_in_at')
            ->get(['student_id', 'checked_in_at']);

        $firstTrial = $trialRows
            ->groupBy('student_id')
            ->map(fn (Collection $rows) => $rows->first()->checked_in_at);

        $trialIds = $firstTrial->keys()->map(fn ($id) => (int) $id)->values();

        $converted = $this->convertedTrialIds($trialIds, $firstTrial);

        $boughtIds = StudentCharge::query()
            ->whereIn('student_id', $this->activeStudentIds)
            ->where('status', StudentCharge::STATUS_PAID)
            ->whereBetween('paid_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        $withoutPlan = $this->activeStudentIds
            ->diff($recurring)
            ->diff($boughtIds)
            ->values();

        $withoutPlanPrevious = $this->withoutPlanPrevious($prevFrom, $prevTo);

        $recurringCount = $recurring->count();
        $newCount = $newIds->count();
        $trialCount = $trialIds->count();
        $convertedCount = $converted->count();

        $composition = $this->composition($recurring, $trialIds, $converted, $newIds);

        return [
            'total_active' => $this->activeStudentIds->count(),
            'recurring' => $recurringCount,
            'recurring_previous' => $recurringPrevious->count(),
            'recurring_change' => $this->percentChange($recurringCount, $recurringPrevious->count()),
            'new' => $newCount,
            'new_previous' => $newPrevious,
            'new_change' => $this->percentChange($newCount, $newPrevious),
            'trial' => $trialCount,
            'trial_converted' => $convertedCount,
            'trial_conversion_rate' => $trialCount > 0
                ? round(($convertedCount / $trialCount) * 100, 1)
                : null,
            'without_plan' => $withoutPlan->count(),
            'without_plan_previous' => $withoutPlanPrevious,
            'without_plan_change' => $this->percentChange($withoutPlan->count(), $withoutPlanPrevious),
            'composition' => $composition,
            'composition_total' => array_sum(array_column($composition, 'value')),
            'series' => $this->studentSeries($buckets),
            'highlight' => $this->studentHighlight($recurring, $newIds, $trialIds, $converted, $withoutPlan),
        ];
    }

    /**
     * @param  Collection<int, int>  $trialIds
     * @param  Collection<int, Carbon>  $firstTrial
     * @return Collection<int, int>
     */
    private function convertedTrialIds(Collection $trialIds, Collection $firstTrial): Collection
    {
        if ($trialIds->isEmpty()) {
            return collect();
        }

        $subscriptions = StudentSubscription::query()
            ->whereIn('student_id', $trialIds)
            ->where('status', StudentSubscription::STATUS_ACTIVE)
            ->get(['student_id', 'created_at'])
            ->groupBy('student_id');

        $purchases = StudentCharge::query()
            ->whereIn('student_id', $trialIds)
            ->where('status', StudentCharge::STATUS_PAID)
            ->whereIn('type', [StudentCharge::TYPE_PLAN, StudentCharge::TYPE_DAY_PASS, StudentCharge::TYPE_CLASS])
            ->get(['student_id', 'paid_at', 'created_at'])
            ->groupBy('student_id');

        return $trialIds
            ->filter(function (int $studentId) use ($subscriptions, $purchases, $firstTrial) {
                $trialAt = $firstTrial->get($studentId);

                if (! $trialAt) {
                    return false;
                }

                $hasSubscription = $subscriptions->get($studentId, collect())
                    ->contains(fn ($subscription) => $subscription->created_at && $subscription->created_at->gte($trialAt));

                if ($hasSubscription) {
                    return true;
                }

                return $purchases->get($studentId, collect())
                    ->contains(function ($charge) use ($trialAt) {
                        $moment = $charge->paid_at ?? $charge->created_at;

                        return $moment && $moment->gte($trialAt);
                    });
            })
            ->values();
    }

    private function withoutPlanPrevious(Carbon $from, Carbon $to): int
    {
        $studentIds = StudentUnit::query()
            ->whereIn('unit_id', $this->unitIds)
            ->where('status', 'active')
            ->distinct()
            ->pluck('student_id');

        $activeIds = Student::query()
            ->whereIn('id', $studentIds)
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $recurring = $this->recurringStudentIds($to);

        $bought = StudentCharge::query()
            ->whereIn('student_id', $activeIds)
            ->where('status', StudentCharge::STATUS_PAID)
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id);

        return $activeIds->diff($recurring)->diff($bought)->count();
    }

    /**
     * @return Collection<int, int>
     */
    private function recurringStudentIds(Carbon $date): Collection
    {
        if ($this->activeStudentIds->isEmpty()) {
            return collect();
        }

        return StudentSubscription::query()
            ->whereIn('student_id', $this->activeStudentIds)
            ->where('status', StudentSubscription::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $date->copy()->endOfDay()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $date->copy()->startOfDay()))
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, int>  $recurring
     * @param  Collection<int, int>  $trialIds
     * @param  Collection<int, int>  $converted
     * @param  Collection<int, int>  $newIds
     * @return array<int, array<string, mixed>>
     */
    private function composition(Collection $recurring, Collection $trialIds, Collection $converted, Collection $newIds): array
    {
        $recurring = $recurring->unique()->values();
        $remaining = $this->activeStudentIds->diff($recurring);

        $trialComposition = $remaining
            ->intersect($trialIds->diff($converted))
            ->values();

        $remaining = $remaining->diff($trialComposition);

        $newComposition = $remaining->intersect($newIds)->values();
        $remaining = $remaining->diff($newComposition);

        return [
            [
                'key' => 'recurring',
                'label' => 'Recorrentes',
                'value' => $recurring->count(),
                'description' => 'Com plano ativo',
            ],
            [
                'key' => 'trial',
                'label' => 'Experimentais',
                'value' => $trialComposition->count(),
                'description' => 'Aula experimental sem conversão',
            ],
            [
                'key' => 'new',
                'label' => 'Novos',
                'value' => $newComposition->count(),
                'description' => 'Cadastrados no período',
            ],
            [
                'key' => 'without_plan',
                'label' => 'Sem plano',
                'value' => $remaining->count(),
                'description' => 'Sem plano ou consumo no período',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $buckets
     * @return array<string, mixed>
     */
    private function studentSeries(array $buckets): array
    {
        $labels = [];
        $newStudents = [];
        $trials = [];
        $ranges = [];

        foreach ($buckets as $bucket) {
            $start = $bucket['from']->copy()->startOfDay();
            $end = $bucket['to']->copy()->endOfDay();

            $labels[] = $bucket['label'];
            $newStudents[] = Student::query()
                ->whereIn('id', $this->activeStudentIds)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $trials[] = StudentCheckin::query()
                ->whereIn('unit_id', $this->unitIds)
                ->where('type', StudentCheckin::TYPE_TRIAL)
                ->whereIn('student_id', $this->activeStudentIds)
                ->whereBetween('checked_in_at', [$start, $end])
                ->distinct()
                ->count('student_id');

            $ranges[] = [
                'from' => $bucket['from']->toDateString(),
                'to' => $bucket['to']->toDateString(),
            ];
        }

        return [
            'labels' => $labels,
            'new' => $newStudents,
            'trials' => $trials,
            'ranges' => $ranges,
        ];
    }

    /**
     * @param  Collection<int, int>  $recurring
     * @param  Collection<int, int>  $newIds
     * @param  Collection<int, int>  $trialIds
     * @param  Collection<int, int>  $converted
     * @param  Collection<int, int>  $withoutPlan
     * @return array<string, mixed>
     */
    private function studentHighlight(
        Collection $recurring,
        Collection $newIds,
        Collection $trialIds,
        Collection $converted,
        Collection $withoutPlan
    ): array {
        $segment = $this->segment ?? 'recurring';

        $ids = match ($segment) {
            'new' => $newIds,
            'trial' => $trialIds,
            'converted' => $converted,
            'without_plan' => $withoutPlan,
            default => $recurring,
        };

        $students = Student::query()
            ->whereIn('id', $ids->take(12)->values())
            ->with(['units' => fn ($query) => $query
                ->whereIn('unit_id', $this->unitIds)
                ->with('unit:id,name')])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'created_at']);

        return [
            'segment' => $segment,
            'label' => match ($segment) {
                'new' => 'Novos alunos no período',
                'trial' => 'Alunos em aula experimental',
                'converted' => 'Experimentais convertidos',
                'without_plan' => 'Alunos sem plano ou consumo',
                default => 'Alunos recorrentes',
            },
            'total' => $ids->count(),
            'students' => $students,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $paidRows
     * @param  Collection<int, array<string, mixed>>  $pendingRows
     * @return Collection<int, array<string, mixed>>
     */
    private function transactions(Collection $paidRows, Collection $pendingRows): Collection
    {
        $rows = collect();

        if ($this->status === null || $this->status === 'paid') {
            $rows = $rows->merge($paidRows->map(fn (array $row) => [
                'charge' => $row['charge'],
                'status' => 'paid',
                'status_label' => 'Recebido',
                'product' => $row['product'],
                'product_label' => self::PRODUCTS[$row['product']],
                'method_label' => $this->methodLabel($row['methods']),
                'amount' => $row['amount'],
                'date' => $row['charge']->paid_at,
            ]));
        }

        if ($this->status === null || in_array($this->status, ['pending_future', 'overdue'], true)) {
            $rows = $rows->merge(
                $pendingRows
                    ->when($this->status === 'pending_future', fn (Collection $rows) => $rows->where('bucket', 'future'))
                    ->when($this->status === 'overdue', fn (Collection $rows) => $rows->where('bucket', 'overdue'))
                    ->map(fn (array $row) => [
                        'charge' => $row['charge'],
                        'status' => $row['bucket'],
                        'status_label' => $row['bucket'] === 'overdue' ? 'Vencido' : 'A receber',
                        'product' => $row['product'],
                        'product_label' => self::PRODUCTS[$row['product']],
                        'method_label' => $this->methodLabel($row['methods']),
                        'amount' => $row['amount'],
                        'date' => $row['charge']->due_date,
                    ])
            );
        }

        return $rows
            ->sortByDesc(fn (array $row) => $row['date']?->getTimestamp() ?? 0)
            ->take(30)
            ->values();
    }

    /**
     * @param  array<string, float>  $methods
     */
    private function methodLabel(array $methods): string
    {
        if (count($methods) === 0) {
            return '—';
        }

        if (count($methods) > 1) {
            return 'Misto';
        }

        $method = array_key_first($methods);

        return self::METHODS[$method] ?? $method;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBuckets(): array
    {
        $days = (int) $this->from->diffInDays($this->to) + 1;
        $buckets = [];

        if ($days <= 92) {
            $cursor = $this->from->copy();

            while ($cursor->lte($this->to)) {
                $end = $cursor->copy()->addDays(6);
                $end = $end->gt($this->to) ? $this->to->copy() : $end;

                $buckets[] = [
                    'from' => $cursor->copy(),
                    'to' => $end->copy(),
                    'label' => $cursor->format('d/m'),
                ];

                $cursor = $end->copy()->addDay();
            }

            return $buckets;
        }

        $cursor = $this->from->copy()->startOfMonth();

        while ($cursor->lte($this->to)) {
            $start = $cursor->copy()->lt($this->from) ? $this->from->copy() : $cursor->copy();
            $end = $cursor->copy()->endOfMonth();
            $end = $end->gt($this->to) ? $this->to->copy() : $end;

            $buckets[] = [
                'from' => $start,
                'to' => $end,
                'label' => $cursor->format('m/y'),
            ];

            $cursor = $cursor->copy()->addMonthNoOverflow()->startOfMonth();
        }

        return $buckets;
    }

    private function percentChange(float|int|null $current, float|int|null $previous): ?float
    {
        if ($previous === null || (float) $previous === 0.0) {
            return null;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }
}
