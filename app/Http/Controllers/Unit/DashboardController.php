<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCheckin;
use App\Models\StudentSubscription;
use App\Models\SubscriptionPayment;
use App\Support\UnitPanelContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $unit = UnitPanelContext::unit(request());
        $units = UnitPanelContext::units();

        $students = Student::query()->whereHas('units', fn ($query) => $query->where('unit_id', $unit->id));

        $metrics = [
            'active_students' => (clone $students)->where('status', 'active')->count(),
            'new_students' => (clone $students)->where('created_at', '>=', now()->startOfMonth())->count(),
            'without_access' => (clone $students)->whereDoesntHave('account')->count(),
            'active_subscriptions' => StudentSubscription::query()
                ->where('unit_id', $unit->id)
                ->active()
                ->count(),
            'pending_subscriptions' => StudentSubscription::query()
                ->where('unit_id', $unit->id)
                ->where('status', StudentSubscription::STATUS_PENDING)
                ->count(),
            'assessment_pending' => (clone $students)->whereDoesntHave('assessment')->count(),
            'assessment_review' => (clone $students)
                ->whereHas('assessment', fn ($query) => $query->whereNull('reviewed_at'))
                ->count(),
            'parq_alerts' => (clone $students)
                ->whereHas('assessment', fn ($query) => $query->where('parq_positive', true))
                ->count(),
            'trial_checkins' => StudentCheckin::query()
                ->where('unit_id', $unit->id)
                ->where('type', StudentCheckin::TYPE_TRIAL)
                ->where('checked_in_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        $classesToday = SchoolClass::query()
            ->where('unit_id', $unit->id)
            ->where('weekday', now()->dayOfWeek)
            ->where('status', 'active')
            ->with(['modality:id,name', 'teacher:id,name'])
            ->orderBy('start_time')
            ->get();

        $bookingsToday = ClassBooking::query()
            ->whereIn('class_id', $classesToday->pluck('id'))
            ->whereDate('booked_for', now()->toDateString())
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->get()
            ->groupBy('class_id');

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $subscriptionRevenue = (float) SubscriptionPayment::query()
            ->where('unit_id', $unit->id)
            ->where('status', SubscriptionPayment::STATUS_APPROVED)
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $classRevenue = (float) ClassBooking::query()
            ->where('unit_id', $unit->id)
            ->where('status', ClassBooking::STATUS_CONFIRMED)
            ->where('type', ClassBooking::TYPE_SINGLE)
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $recentStudents = (clone $students)
            ->with(['account', 'assessment'])
            ->latest()
            ->take(5)
            ->get();

        $recentBookings = ClassBooking::query()
            ->where('unit_id', $unit->id)
            ->whereDate('booked_for', '>=', now()->toDateString())
            ->with(['student:id,name', 'schoolClass:id,name'])
            ->orderBy('booked_for')
            ->take(5)
            ->get();

        return view('pages.unit.dashboard', compact(
            'unit',
            'units',
            'metrics',
            'classesToday',
            'bookingsToday',
            'subscriptionRevenue',
            'classRevenue',
            'recentStudents',
            'recentBookings'
        ));
    }
}
