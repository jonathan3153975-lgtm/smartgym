<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAssessment;
use App\Support\AssessmentMetrics;
use App\Support\StudentAssessmentForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentAssessmentController extends Controller
{
    public function show(Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $student->load('assessment');
        $assessment = $student->assessment;

        if (! $assessment?->isCompleted()) {
            return $this->formView($network, $student, $assessment, false);
        }

        return $this->summaryView($network, $student, $assessment);
    }

    public function create(Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $student->load('assessment');
        $latest = $student->assessment;

        return $this->formView($network, $student, $latest, $latest !== null);
    }

    public function history(Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $assessments = $student->assessments()->get();

        return view('pages.student-portal.assessment.history', compact(
            'network',
            'account',
            'student',
            'assessments'
        ));
    }

    public function showOne(Network $network, StudentAssessment $assessment): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        abort_unless(
            (int) $assessment->student_id === (int) $account->student_id
            && (int) $assessment->network_id === (int) $network->id,
            404
        );

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        return $this->summaryView($network, $student, $assessment);
    }

    public function store(Request $request, Network $network): RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        StudentAssessmentForm::normalizeDecimals($request);

        $validated = $request->validate(StudentAssessmentForm::rules());

        $parqAnswers = StudentAssessmentForm::parqAnswers($validated['parq_answers']);

        $payload = Arr::except($validated, ['parq_answers', 'smoker', 'consent']);

        StudentAssessment::create(array_merge($payload, [
            'network_id' => $network->id,
            'student_id' => $student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'smoker' => filter_var($validated['smoker'] ?? false, FILTER_VALIDATE_BOOL),
            'parq_answers' => $parqAnswers,
            'parq_positive' => in_array(true, $parqAnswers, true),
            'consent' => true,
            'submitted_at' => now(),
        ]));

        return redirect()
            ->route('student.portal.assessment.show', $network)
            ->with('success', 'Ficha de avaliação enviada com sucesso!');
    }

    private function formView(
        Network $network,
        Student $student,
        ?StudentAssessment $assessment,
        bool $isReassessment
    ): View {
        return view('pages.student-portal.assessment.form', compact(
            'network',
            'student',
            'assessment',
            'isReassessment'
        ));
    }

    private function summaryView(Network $network, Student $student, StudentAssessment $assessment): View
    {
        $previous = $student->assessments()
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

        return view('pages.student-portal.assessment.show', [
            'network' => $network,
            'student' => $student,
            'assessment' => $assessment,
            'previous' => $previous,
            'comparison' => $this->comparison($assessment, $previous),
            'metrics' => AssessmentMetrics::for($assessment, $previous),
            'totalAssessments' => $student->assessments()->count(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function comparison(StudentAssessment $current, ?StudentAssessment $previous): ?array
    {
        if (! $previous) {
            return null;
        }

        return [
            'date' => $previous->submitted_at,
            'weight' => $this->delta($current->weight_kg, $previous->weight_kg),
            'body_fat' => $this->delta($current->body_fat_percent, $previous->body_fat_percent),
            'waist' => $this->delta($current->waist_cm, $previous->waist_cm),
            'bmi' => $this->delta($current->bmi, $previous->bmi),
            'parq_changed' => $current->parq_positive !== $previous->parq_positive,
            'parq_positive' => $current->parq_positive,
        ];
    }

    private function delta(mixed $current, mixed $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return round((float) $current - (float) $previous, 2);
    }

    private function ensureActivePlan(StudentAccount $account, Network $network): ?RedirectResponse
    {
        if ($account->student?->activeSubscription()) {
            return null;
        }

        return redirect()
            ->route('student.portal.plans', $network)
            ->withErrors(['plan' => 'Assine um plano para preencher a ficha de avaliação.']);
    }

    private function account(Network $network): StudentAccount
    {
        /** @var StudentAccount|null $account */
        $account = Auth::guard('student')->user();

        abort_unless(
            $account instanceof StudentAccount && (int) $account->network_id === (int) $network->id,
            403
        );

        $account->loadMissing('student');

        return $account;
    }
}
