<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Support\StudentActivitySummary;
use App\Support\StudentAssessmentComparison;
use App\Support\StudentAssessmentForm;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class StudentAssessmentReviewController extends Controller
{
    public function edit(Request $request, Student $student, StudentAssessment $assessment): View
    {
        $this->authorizeAccess($request, $student, $assessment);

        $previousAssessment = $this->previousAssessment($student, $assessment);

        $periodEnd = $assessment->submitted_at ?? now();
        $periodStart = $previousAssessment?->submitted_at ?? $periodEnd->copy()->subDays(30);

        return view('pages.students.assessment-verify', [
            'student' => $student,
            'assessment' => $assessment,
            'previousAssessment' => $previousAssessment,
            'changes' => StudentAssessmentComparison::changes($assessment, $previousAssessment),
            'activity' => StudentActivitySummary::for($student, $periodStart, $periodEnd),
        ]);
    }

    public function update(Request $request, Student $student, StudentAssessment $assessment): RedirectResponse
    {
        $this->authorizeAccess($request, $student, $assessment);

        StudentAssessmentForm::normalizeDecimals($request);

        $rules = StudentAssessmentForm::rules();
        unset($rules['consent']);

        $rules['trainer_notes'] = ['nullable', 'string', 'max:4000'];

        $validated = $request->validate($rules);

        $parqAnswers = StudentAssessmentForm::parqAnswers($validated['parq_answers']);

        $payload = Arr::except($validated, ['parq_answers', 'smoker', 'trainer_notes']);

        $assessment->update(array_merge($payload, [
            'smoker' => filter_var($validated['smoker'] ?? false, FILTER_VALIDATE_BOOL),
            'parq_answers' => $parqAnswers,
            'parq_positive' => in_array(true, $parqAnswers, true),
            'trainer_notes' => $validated['trainer_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()?->id,
        ]));

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Ficha de avaliação validada com sucesso.');
    }

    private function authorizeAccess(Request $request, Student $student, StudentAssessment $assessment): void
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403);
        abort_unless($tenant->networkId(), 403);
        abort_unless($tenant->canAccessStudent($student), 404);
        abort_unless($request->user()?->canVerifyAssessments(), 403);
        abort_unless((int) $assessment->student_id === (int) $student->id, 404);
        abort_unless($assessment->isCompleted(), 404);
    }

    private function previousAssessment(Student $student, StudentAssessment $assessment): ?StudentAssessment
    {
        return $student->assessments()
            ->whereKeyNot($assessment->id)
            ->where(function ($query) use ($assessment) {
                $query->where('submitted_at', '<', $assessment->submitted_at)
                    ->orWhere(fn ($sameTime) => $sameTime
                        ->where('submitted_at', $assessment->submitted_at)
                        ->where('id', '<', $assessment->id));
            })
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->first();
    }
}
