<?php

namespace App\Http\Controllers;

use App\Mail\StudentChargeMail;
use App\Models\Network;
use App\Models\Region;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentNotification;
use App\Models\StudentSalePayment;
use App\Models\StudentUnit;
use App\Support\Payments\MercadoPagoException;
use App\Support\Payments\StudentChargeService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashController extends Controller
{
    public function __construct(private readonly StudentChargeService $charges) {}

    public function index(Request $request): View
    {
        $scope = $this->resolveScope($request);
        $selectedDate = $this->resolveDate($request);
        $status = $this->resolveStatus($request);
        $period = $this->resolvePeriod($request, $selectedDate);

        $charges = $this->baseQuery($scope['unit_ids'])
            ->when($status, fn ($query) => $this->applyStatusFilter($query, $status, $selectedDate))
            ->when(! $status, fn ($query) => $this->applyAllFilter($query, $selectedDate))
            ->orderByRaw(
                "case when status = 'pending' and due_date < ? then 0 when status = 'pending' then 1 else 2 end",
                [today()->toDateString()]
            )
            ->orderByDesc('due_date')
            ->orderByDesc('paid_at')
            ->paginate(20)
            ->withQueryString();

        $installments = [];
        $chargeActions = [];

        foreach ($charges as $charge) {
            $plan = $this->installmentPlan($charge);

            if ($plan) {
                $installments[$charge->id] = $plan;
            }

            $chargeActions[$charge->id] = $this->chargeActions($charge);
        }

        return view('pages.management.cash.index', [
            'scope' => $scope,
            'filters' => [
                'network_id' => $scope['network_id'],
                'region_id' => $scope['region_id'],
                'unit_id' => $scope['unit_id'],
                'date' => $selectedDate->toDateString(),
                'period' => $period,
                'status' => $status ?? 'all',
            ],
            'selectedDate' => $selectedDate,
            'period' => $period,
            'status' => $status,
            'charges' => $charges,
            'totals' => $this->totals($scope['unit_ids'], $selectedDate),
            'installments' => $installments,
            'chargeActions' => $chargeActions,
            'saleStudents' => $this->saleStudents($scope['unit_ids']),
        ]);
    }

    public function receipt(Request $request, StudentCharge $charge): View
    {
        $this->ensureCharge($charge);

        $charge->load(['student', 'unit', 'network', 'sale.payments', 'subscription']);

        $document = $request->string('doc')->toString() === 'comprovante' ? 'comprovante' : 'recibo';

        return view('pages.management.cash.receipt', [
            'charge' => $charge,
            'document' => $document,
            'methodLabel' => $this->methodLabel($charge),
        ]);
    }

    public function confirm(Request $request, StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($charge);

        if (! $charge->isPending()) {
            return back()->withErrors(['charge' => 'Esta cobrança não está pendente.']);
        }

        $this->applyInterest($request, $charge);

        $method = $request->string('method')->toString() ?: 'manual';

        if (! array_key_exists($method, StudentSalePayment::METHODS) && $method !== 'manual') {
            $method = 'manual';
        }

        $this->charges->markPaid($charge, $method, auth()->id());

        return back()->with('success', 'Pagamento confirmado e cobrança baixada.');
    }

    public function pix(Request $request, StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($charge);

        $this->applyInterest($request, $charge);

        try {
            $this->charges->createPix($charge);
        } catch (MercadoPagoException|ValidationException $exception) {
            return back()->withErrors(['charge' => $exception->getMessage()]);
        }

        return back()->with('success', 'QR Code Pix gerado para a cobrança.');
    }

    public function email(Request $request, StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($charge);

        $this->applyInterest($request, $charge);

        if (! $charge->student?->email) {
            return back()->withErrors(['charge' => 'O aluno não possui e-mail cadastrado.']);
        }

        $portalUrl = $charge->network
            ? route('student.portal.finance.index', $charge->network)
            : url('/');

        Mail::to($charge->student->email)->send(new StudentChargeMail($charge, $charge->student, $portalUrl));

        $charge->update(['updated_by' => auth()->id()]);

        return back()->with('success', 'Cobrança enviada por e-mail.');
    }

    public function notify(StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($charge);

        if (! $charge->isPending()) {
            return back()->withErrors(['charge' => 'Somente cobranças pendentes podem ser notificadas.']);
        }

        StudentNotification::create([
            'network_id' => $charge->network_id,
            'student_id' => $charge->student_id,
            'title' => 'Cobrança em aberto',
            'message' => 'Sua cobrança de R$ '.number_format((float) $charge->amount, 2, ',', '.')
                .' referente a "'.$charge->description.'"'
                .($charge->due_date ? ' venceu em '.$charge->due_date->format('d/m/Y') : '')
                .'. Regularize para evitar bloqueios de acesso.',
            'type' => 'payment_reminder',
        ]);

        return back()->with('success', 'Notificação enviada para o portal do aluno.');
    }

    public function link(Request $request, StudentCharge $charge): JsonResponse
    {
        $this->ensureCharge($charge);

        try {
            $this->applyInterest($request, $charge);
            $checkout = $this->charges->createCheckoutLink($charge, auth()->id());
        } catch (MercadoPagoException|ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'url' => $checkout['url'],
            'message' => 'Link de pagamento gerado.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveScope(Request $request): array
    {
        $tenant = TenantContext::current();
        $units = $tenant->unitsQuery()->get(['id', 'name', 'network_id', 'region_id']);

        $networkId = null;
        $regionId = null;
        $unitId = null;

        if ($tenant->isPlatform()) {
            $requested = (int) $request->input('network_id', 0);

            if ($requested > 0 && $units->contains(fn ($unit) => (int) $unit->network_id === $requested)) {
                $networkId = $requested;
                $units = $units->where('network_id', $requested);
            }
        } else {
            $networkId = $tenant->networkId();
        }

        if (in_array($tenant->level(), ['platform', 'network'], true)) {
            $requested = (int) $request->input('region_id', 0);

            if ($requested > 0 && $units->contains(fn ($unit) => (int) $unit->region_id === $requested)) {
                $regionId = $requested;
                $units = $units->where('region_id', $requested);
            }
        } elseif ($tenant->level() === 'region') {
            $regionId = $tenant->regionId();
        }

        if (in_array($tenant->level(), ['platform', 'network', 'region'], true)) {
            $requested = (int) $request->input('unit_id', 0);

            if ($requested > 0 && $units->contains(fn ($unit) => (int) $unit->id === $requested)) {
                $unitId = $requested;
                $units = $units->where('id', $requested);
            }
        }

        if ($tenant->level() === 'unit') {
            $unitId = $tenant->unitId();
            $units = $units->where('id', $unitId);
        }

        $units = $units->sortBy('name')->values();

        $regionIds = $units->pluck('region_id')->filter()->unique();

        return [
            'level' => $tenant->level(),
            'level_label' => match ($tenant->level()) {
                'platform' => 'Proprietário / plataforma',
                'network' => 'Rede',
                'region' => 'Região',
                default => 'Unidade',
            },
            'network_id' => $networkId,
            'region_id' => $regionId,
            'unit_id' => $unitId,
            'unit_ids' => $units->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'units' => $units,
            'regions' => Region::query()->whereIn('id', $regionIds)->orderBy('name')->get(['id', 'name']),
            'networks' => $tenant->isPlatform()
                ? Network::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'network_filter' => $tenant->isPlatform(),
            'region_filter' => in_array($tenant->level(), ['platform', 'network'], true),
            'unit_filter' => in_array($tenant->level(), ['platform', 'network', 'region'], true),
            'network_name' => $networkId ? Network::find($networkId, ['id', 'name'])?->name : null,
            'region_name' => $regionId ? Region::find($regionId, ['id', 'name'])?->name : null,
            'unit_name' => $unitId ? $units->firstWhere('id', $unitId)?->name : null,
            'unit_count' => $units->count(),
        ];
    }

    private function resolveDate(Request $request): Carbon
    {
        if ($request->filled('date')) {
            return Carbon::parse((string) $request->input('date'))->startOfDay();
        }

        return match ($request->string('period')->toString()) {
            'yesterday' => today()->subDay(),
            'tomorrow' => today()->addDay(),
            default => today(),
        };
    }

    private function resolvePeriod(Request $request, Carbon $selectedDate): string
    {
        if ($request->filled('date')) {
            return 'custom';
        }

        $period = $request->string('period')->toString();

        return in_array($period, ['today', 'yesterday', 'tomorrow'], true) ? $period : 'today';
    }

    private function resolveStatus(Request $request): ?string
    {
        $status = $request->string('status')->toString();

        return in_array($status, ['paid', 'pending', 'overdue'], true) ? $status : null;
    }

    /**
     * @param  array<int, int>  $unitIds
     */
    private function baseQuery(array $unitIds)
    {
        return StudentCharge::query()
            ->whereIn('unit_id', $unitIds)
            ->whereIn('status', [StudentCharge::STATUS_PAID, StudentCharge::STATUS_PENDING])
            ->with([
                'student:id,name,email,phone,whatsapp',
                'unit:id,name',
                'network:id,name,slug',
                'subscription:id,plan_name,plan_type',
                'sale:id,code,launched_at',
                'sale.payments',
            ]);
    }

    private function applyStatusFilter($query, string $status, Carbon $selectedDate): void
    {
        if ($status === 'paid') {
            $query->where('status', StudentCharge::STATUS_PAID)
                ->whereDate('paid_at', $selectedDate->toDateString());

            return;
        }

        if ($status === 'pending') {
            $query->where('status', StudentCharge::STATUS_PENDING)
                ->whereDate('due_date', today()->toDateString());

            return;
        }

        $this->applyOverdueFilter($query, $selectedDate);
    }

    private function applyAllFilter($query, Carbon $selectedDate): void
    {
        $query->where(function ($builder) use ($selectedDate) {
            $builder->where(function ($paid) use ($selectedDate) {
                $paid->where('status', StudentCharge::STATUS_PAID)
                    ->whereDate('paid_at', $selectedDate->toDateString());
            });

            if ($selectedDate->gte(today())) {
                // Pending means "due today" for the cash operator.
                $builder->orWhere(function ($pending) {
                    $pending->where('status', StudentCharge::STATUS_PENDING)
                        ->whereDate('due_date', today()->toDateString());
                });
            }

            if ($selectedDate->gt(today())) {
                $builder->orWhere(function ($upcoming) use ($selectedDate) {
                    $upcoming->where('status', StudentCharge::STATUS_PENDING)
                        ->whereDate('due_date', $selectedDate->toDateString());
                });
            }

            $builder->orWhere(function ($overdue) use ($selectedDate) {
                $this->applyOverdueFilter($overdue, $selectedDate);
            });
        });
    }

    private function applyOverdueFilter($query, Carbon $selectedDate): void
    {
        $query->where('status', StudentCharge::STATUS_PENDING)
            ->whereDate('due_date', '<', today()->toDateString());

        if ($selectedDate->lt(today())) {
            $query->whereDate('due_date', '<=', $selectedDate->toDateString());
        }
    }

    /**
     * @param  array<int, int>  $unitIds
     * @return array<string, mixed>
     */
    private function totals(array $unitIds, Carbon $selectedDate): array
    {
        $receivedQuery = $this->baseQuery($unitIds)
            ->where('status', StudentCharge::STATUS_PAID)
            ->whereDate('paid_at', $selectedDate->toDateString());

        $pendingQuery = $this->baseQuery($unitIds)
            ->where('status', StudentCharge::STATUS_PENDING)
            ->whereDate('due_date', today()->toDateString());

        $overdueQuery = $this->baseQuery($unitIds);
        $this->applyOverdueFilter($overdueQuery, $selectedDate);

        return [
            'received' => round((float) (clone $receivedQuery)->sum('amount'), 2),
            'received_count' => (clone $receivedQuery)->count(),
            'pending' => round((float) (clone $pendingQuery)->sum('amount'), 2),
            'pending_count' => (clone $pendingQuery)->count(),
            'overdue' => round((float) (clone $overdueQuery)->sum('amount'), 2),
            'overdue_count' => (clone $overdueQuery)->count(),
        ];
    }

    /**
     * @param  array<int, int>  $unitIds
     * @return Collection<int, Student>
     */
    private function saleStudents(array $unitIds)
    {
        $studentIds = StudentUnit::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->distinct()
            ->pluck('student_id');

        return Student::query()
            ->whereIn('id', $studentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function installmentPlan(StudentCharge $charge): ?array
    {
        $payments = $charge->sale?->payments?->where('installments', '>', 1) ?? collect();

        if ($payments->isEmpty()) {
            return null;
        }

        $items = [];
        $number = 1;

        foreach ($payments as $payment) {
            $count = max(1, (int) $payment->installments);
            $amount = (float) $payment->amount;
            $base = round($amount / $count, 2);
            $start = $payment->created_at?->copy() ?? $charge->paid_at?->copy() ?? now();

            for ($index = 0; $index < $count; $index++) {
                $part = $index === $count - 1
                    ? round($amount - ($base * ($count - 1)), 2)
                    : $base;

                $items[] = [
                    'number' => $number++,
                    'method' => $payment->method_label,
                    'due_date' => $start->copy()->addMonthsNoOverflow($index)->toDateString(),
                    'amount' => $part,
                ];
            }
        }

        return [
            'count' => count($items),
            'total' => round(array_sum(array_column($items, 'amount')), 2),
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function chargeActions(StudentCharge $charge): array
    {
        $metadata = $charge->metadata ?? [];
        $isOverdue = $charge->isPending()
            && $charge->due_date !== null
            && $charge->due_date->lt(today());

        return [
            'id' => $charge->id,
            'student' => $charge->student?->name ?? 'Aluno',
            'phone' => $charge->student?->whatsapp ?: $charge->student?->phone,
            'description' => $charge->description,
            'method' => $this->methodLabel($charge),
            'amount' => 'R$ '.number_format((float) $charge->amount, 2, ',', '.'),
            'amount_value' => (float) ($metadata['original_amount'] ?? $charge->amount),
            'is_overdue' => $isOverdue,
            'interest_percent' => $metadata['interest_percent'] ?? null,
            'interest_amount' => $metadata['interest_amount'] ?? null,
            'due_date' => $charge->due_date?->format('d/m/Y'),
            'pix_url' => route('management.cash.pix', $charge),
            'email_url' => route('management.cash.email', $charge),
            'link_url' => route('management.cash.link', $charge),
            'confirm_url' => route('management.cash.confirm', $charge),
        ];
    }

    /**
     * Apply late interest to an overdue charge before generating or confirming
     * a collection. The original amount is preserved in the metadata so the
     * calculation never compounds on repeated generations.
     */
    private function applyInterest(Request $request, StudentCharge $charge): float
    {
        $validated = $request->validate([
            'interest_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interest_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $percent = (float) ($validated['interest_percent'] ?? 0);
        $fixedAmount = (float) ($validated['interest_amount'] ?? 0);

        $isOverdue = $charge->isPending()
            && $charge->due_date !== null
            && $charge->due_date->lt(today());

        if (! $isOverdue) {
            return (float) $charge->amount;
        }

        $metadata = $charge->metadata ?? [];
        $base = (float) ($metadata['original_amount'] ?? $charge->amount);

        $interest = $fixedAmount > 0
            ? round($fixedAmount, 2)
            : round($base * ($percent / 100), 2);

        if ($interest <= 0) {
            return (float) $charge->amount;
        }

        $total = round($base + $interest, 2);

        $charge->update([
            'amount' => $total,
            'metadata' => array_merge($metadata, [
                'original_amount' => $base,
                'interest_amount' => $interest,
                'interest_percent' => $fixedAmount > 0 ? null : $percent,
            ]),
            'updated_by' => auth()->id(),
        ]);

        return $total;
    }

    private function methodLabel(StudentCharge $charge): string
    {
        $methods = $charge->sale?->payments?->pluck('method')->unique() ?? collect();

        if ($methods->count() === 1) {
            return StudentSalePayment::METHODS[$methods->first()] ?? $methods->first();
        }

        if ($methods->count() > 1) {
            return 'Misto';
        }

        if ($charge->payment_method) {
            return StudentSalePayment::METHODS[$charge->payment_method] ?? ucfirst($charge->payment_method);
        }

        return 'A definir';
    }

    private function ensureCharge(StudentCharge $charge): void
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->unitsQuery()->whereKey($charge->unit_id)->exists(), 404);

        $charge->loadMissing(['student', 'unit', 'network', 'sale.payments']);
    }
}
