<?php

namespace App\Support;

use App\Models\ClassBooking;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentCheckin;
use App\Models\StudentSale;
use App\Models\StudentSaleItem;
use App\Models\StudentSubscription;
use Illuminate\Support\Collection;

class StudentAccessSummary
{
    /**
     * Build the access summary for a single student.
     *
     * @return array{
     *     has_active_plan: bool,
     *     plans: array<int, array{name: string, ends_at: string|null}>,
     *     day_pass: bool,
     *     classes: array<int, string>,
     *     trial_checkin: bool,
     *     is_empty: bool
     * }
     */
    public static function for(Student $student): array
    {
        return self::forMany(collect([$student]))[$student->id] ?? self::empty();
    }

    /**
     * Build access summaries for a list of students using a single query per source.
     *
     * @param  Collection<int, Student>  $students
     * @return array<int, array{
     *     has_active_plan: bool,
     *     plans: array<int, array{name: string, ends_at: string|null}>,
     *     day_pass: bool,
     *     classes: array<int, string>,
     *     trial_checkin: bool,
     *     is_empty: bool
     * }>
     */
    public static function forMany(Collection $students): array
    {
        $studentIds = $students->pluck('id')->filter()->unique()->values();

        if ($studentIds->isEmpty()) {
            return [];
        }

        $today = now()->toDateString();

        $subscriptions = StudentSubscription::query()
            ->whereIn('student_id', $studentIds)
            ->active()
            ->with('membershipPlan:id,name')
            ->get(['id', 'student_id', 'membership_plan_id', 'plan_name', 'ends_at'])
            ->groupBy('student_id');

        $dayPasses = StudentSale::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('items', function ($query) use ($today) {
                $query->where('type', StudentSaleItem::TYPE_DAY_PASS)
                    ->where('status', StudentCharge::STATUS_PAID)
                    ->where(function ($inner) use ($today) {
                        $inner->whereDate('launched_at', $today)
                            ->orWhere(function ($range) use ($today) {
                                $range->whereDate('starts_at', '<=', $today)
                                    ->whereDate('ends_at', '>=', $today);
                            });
                    });
            })
            ->pluck('student_id')
            ->unique()
            ->flip();

        $bookings = ClassBooking::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('booked_for', $today)
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->with(['schoolClass:id,name', 'schedule:id,start_time'])
            ->get(['id', 'student_id', 'class_id', 'class_schedule_id'])
            ->groupBy('student_id');

        $trialCheckins = StudentCheckin::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', StudentCheckin::TYPE_TRIAL)
            ->whereDate('checked_in_at', $today)
            ->pluck('student_id')
            ->unique()
            ->flip();

        $summaries = [];

        foreach ($studentIds as $studentId) {
            $plans = ($subscriptions[$studentId] ?? collect())
                ->map(fn (StudentSubscription $subscription) => [
                    'name' => $subscription->plan_name ?: ($subscription->membershipPlan?->name ?? 'Plano'),
                    'ends_at' => $subscription->ends_at?->format('d/m/Y'),
                ])
                ->values()
                ->all();

            $classes = ($bookings[$studentId] ?? collect())
                ->map(function (ClassBooking $booking) {
                    $name = $booking->schoolClass?->name ?? 'Aula';
                    $time = $booking->schedule?->time_label;

                    return $time ? $name.' '.$time : $name;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $summaries[$studentId] = [
                'has_active_plan' => $plans !== [],
                'plans' => $plans,
                'day_pass' => $dayPasses->has($studentId),
                'classes' => $classes,
                'trial_checkin' => $trialCheckins->has($studentId),
                'is_empty' => $plans === []
                    && ! $dayPasses->has($studentId)
                    && $classes === []
                    && ! $trialCheckins->has($studentId),
            ];
        }

        return $summaries;
    }

    /**
     * @return array{
     *     has_active_plan: bool,
     *     plans: array<int, array{name: string, ends_at: string|null}>,
     *     day_pass: bool,
     *     classes: array<int, string>,
     *     trial_checkin: bool,
     *     is_empty: bool
     * }
     */
    private static function empty(): array
    {
        return [
            'has_active_plan' => false,
            'plans' => [],
            'day_pass' => false,
            'classes' => [],
            'trial_checkin' => false,
            'is_empty' => true,
        ];
    }
}
