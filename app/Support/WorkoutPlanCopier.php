<?php

namespace App\Support;

use App\Models\WorkoutPlan;
use Illuminate\Support\Facades\DB;

class WorkoutPlanCopier
{
    /**
     * Copy a workout plan (sessions and exercises) into a new plan.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function copy(WorkoutPlan $source, array $attributes = []): WorkoutPlan
    {
        return DB::transaction(function () use ($source, $attributes) {
            $source->loadMissing('sessions.exercises');

            $plan = WorkoutPlan::create(array_merge([
                'network_id' => $source->network_id,
                'name' => $source->name,
                'objective' => $source->objective,
                'notes' => $source->notes,
                'visibility' => WorkoutPlan::VISIBILITY_PRIVATE,
                'status' => WorkoutPlan::STATUS_ACTIVE,
                'source_plan_id' => $source->id,
            ], $attributes));

            foreach ($source->sessions as $session) {
                $newSession = $plan->sessions()->create([
                    'name' => $session->name,
                    'notes' => $session->notes,
                    'order' => $session->order,
                ]);

                foreach ($session->exercises as $exercise) {
                    $newSession->exercises()->create([
                        'exercise_id' => $exercise->exercise_id,
                        'exercise_name' => $exercise->exercise_name,
                        'muscle_group' => $exercise->muscle_group,
                        'order' => $exercise->order,
                        'sets' => $exercise->sets,
                        'reps' => $exercise->reps,
                        'rest_seconds' => $exercise->rest_seconds,
                        'suggested_load' => $exercise->suggested_load,
                        'notes' => $exercise->notes,
                    ]);
                }
            }

            return $plan;
        });
    }

    public function activateForStudent(WorkoutPlan $plan): void
    {
        if (! $plan->student_id) {
            return;
        }

        WorkoutPlan::query()
            ->where('student_id', $plan->student_id)
            ->where('network_id', $plan->network_id)
            ->whereKeyNot($plan->id)
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->update(['status' => WorkoutPlan::STATUS_INACTIVE]);
    }
}
