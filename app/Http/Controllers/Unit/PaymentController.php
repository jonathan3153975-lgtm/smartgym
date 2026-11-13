<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\StudentSubscription;
use App\Models\SubscriptionPayment;
use App\Support\UnitPanelContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $unit = UnitPanelContext::unit($request);
        $units = UnitPanelContext::units();

        $query = StudentSubscription::query()
            ->where('unit_id', $unit->id)
            ->with(['student:id,name,email', 'payments'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('student', fn ($builder) => $builder->where('name', 'like', "%{$search}%"));
        }

        $status = $request->string('status')->toString();

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'pending') {
            $query->where('status', StudentSubscription::STATUS_PENDING);
        } elseif ($status === 'expiring') {
            $query->where('status', StudentSubscription::STATUS_ACTIVE)
                ->whereBetween('ends_at', [now(), now()->addDays(7)]);
        } elseif ($status === 'expired') {
            $query->where(function ($builder) {
                $builder->where('status', StudentSubscription::STATUS_EXPIRED)
                    ->orWhere(fn ($active) => $active
                        ->where('status', StudentSubscription::STATUS_ACTIVE)
                        ->where('ends_at', '<', now()));
            });
        }

        $subscriptions = $query->paginate(20)->withQueryString();

        $metrics = [
            'active' => StudentSubscription::query()->where('unit_id', $unit->id)->active()->count(),
            'pending' => StudentSubscription::query()
                ->where('unit_id', $unit->id)
                ->where('status', StudentSubscription::STATUS_PENDING)
                ->count(),
            'expiring' => StudentSubscription::query()
                ->where('unit_id', $unit->id)
                ->where('status', StudentSubscription::STATUS_ACTIVE)
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->count(),
            'revenue' => (float) SubscriptionPayment::query()
                ->where('unit_id', $unit->id)
                ->where('status', SubscriptionPayment::STATUS_APPROVED)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'class_revenue' => (float) ClassBooking::query()
                ->where('unit_id', $unit->id)
                ->where('status', ClassBooking::STATUS_CONFIRMED)
                ->where('type', ClassBooking::TYPE_SINGLE)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ];

        $recentPayments = SubscriptionPayment::query()
            ->where('unit_id', $unit->id)
            ->with(['subscription.student:id,name'])
            ->orderByDesc('paid_at')
            ->take(10)
            ->get();

        $pendingClassBookings = ClassBooking::query()
            ->where('unit_id', $unit->id)
            ->where('status', ClassBooking::STATUS_PENDING)
            ->with(['student:id,name', 'schoolClass:id,name'])
            ->orderBy('booked_for')
            ->take(10)
            ->get();

        return view('pages.unit.payments.index', compact(
            'unit',
            'units',
            'subscriptions',
            'metrics',
            'recentPayments',
            'pendingClassBookings',
            'status'
        ));
    }

    public function confirm(Request $request, StudentSubscription $subscription): RedirectResponse
    {
        $unit = UnitPanelContext::unit($request);

        abort_unless((int) $subscription->unit_id === (int) $unit->id, 404);

        if ($subscription->isActive()) {
            return redirect()->back()->withErrors([
                'payment' => 'Esta assinatura já está ativa.',
            ]);
        }

        DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => StudentSubscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'ends_at' => now()->addDays(max(1, (int) $subscription->duration_days ?: 30)),
                'paid_at' => now(),
            ]);

            $payment = $subscription->payments()
                ->where('status', SubscriptionPayment::STATUS_PENDING)
                ->latest()
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => SubscriptionPayment::STATUS_APPROVED,
                    'paid_at' => now(),
                    'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                        'manual_confirmation' => true,
                        'confirmed_by' => auth()->id(),
                    ]),
                ]);

                return;
            }

            $subscription->payments()->create([
                'network_id' => $subscription->network_id,
                'unit_id' => $subscription->unit_id,
                'provider' => 'manual',
                'status' => SubscriptionPayment::STATUS_APPROVED,
                'amount' => $subscription->price,
                'external_reference' => 'manual:'.$subscription->id,
                'paid_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Pagamento confirmado manualmente e assinatura ativada.');
    }
}
