<?php

namespace App\Http\Controllers;

use App\Models\BodyMeasurement;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentMeasurementController extends Controller
{
    public function index(Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $student->load('assessment');

        $measurements = $student->measurements()->get();
        $latest = $measurements->first();
        $previous = $measurements->get(1);
        $first = $measurements->last();

        $heightCm = $student->assessment?->height_cm !== null
            ? (float) $student->assessment->height_cm
            : null;

        return view('pages.student-portal.measurements.index', [
            'network' => $network,
            'account' => $account,
            'student' => $student,
            'measurements' => $measurements,
            'latest' => $latest,
            'previous' => $previous,
            'first' => $first,
            'heightCm' => $heightCm,
            'latestBmi' => $latest?->bmiForHeight($heightCm),
            'weightDelta' => $latest && $previous ? (float) $latest->weight_kg - (float) $previous->weight_kg : null,
            'weightDeltaTotal' => $latest && $first && $latest->id !== $first->id
                ? (float) $latest->weight_kg - (float) $first->weight_kg
                : null,
            'chart' => $this->chart($measurements),
        ]);
    }

    public function store(Request $request, Network $network): RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $validated = $this->validateMeasurement($request);

        $measurement = BodyMeasurement::query()
            ->where('student_id', $student->id)
            ->whereDate('measured_at', $validated['measured_at'])
            ->first();

        $payload = array_merge($validated, [
            'network_id' => $network->id,
        ]);

        if ($measurement) {
            $measurement->update($payload);
        } else {
            BodyMeasurement::create(array_merge($payload, [
                'student_id' => $student->id,
            ]));
        }

        return redirect()
            ->route('student.portal.measurements.index', $network)
            ->with('success', 'Medidas registradas com sucesso!');
    }

    public function destroy(Network $network, BodyMeasurement $bodyMeasurement): RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        abort_unless(
            (int) $bodyMeasurement->student_id === (int) $account->student_id
            && (int) $bodyMeasurement->network_id === (int) $network->id,
            404
        );

        $bodyMeasurement->delete();

        return redirect()
            ->route('student.portal.measurements.index', $network)
            ->with('success', 'Registro de medidas removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMeasurement(Request $request): array
    {
        return $request->validate([
            'measured_at' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'between:20,400'],
            'body_fat_percent' => ['nullable', 'numeric', 'between:1,70'],
            'waist_cm' => ['nullable', 'numeric', 'between:20,250'],
            'hip_cm' => ['nullable', 'numeric', 'between:20,250'],
            'chest_cm' => ['nullable', 'numeric', 'between:20,250'],
            'arm_cm' => ['nullable', 'numeric', 'between:10,100'],
            'thigh_cm' => ['nullable', 'numeric', 'between:20,150'],
            'calf_cm' => ['nullable', 'numeric', 'between:10,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Build a lightweight SVG line chart for the last 12 measurements.
     *
     * @param  Collection<int, BodyMeasurement>  $measurements
     * @return array{points: array<int, array{x: float, y: float, label: string, weight: float}>, min: float, max: float, count: int}
     */
    private function chart($measurements): array
    {
        $series = $measurements->sortBy('measured_at')->take(-12)->values();
        $count = $series->count();

        if ($count === 0) {
            return ['points' => [], 'min' => 0, 'max' => 0, 'count' => 0];
        }

        $weights = $series->map(fn (BodyMeasurement $measurement) => (float) $measurement->weight_kg);
        $min = $weights->min();
        $max = $weights->max();
        $padding = max(0.5, ($max - $min) * 0.15);

        $min -= $padding;
        $max += $padding;

        if ($max - $min <= 0) {
            $max = $min + 1;
        }

        $points = [];

        foreach ($series as $index => $measurement) {
            $x = $count === 1 ? 300.0 : 30 + ($index * (540 / ($count - 1)));
            $weight = (float) $measurement->weight_kg;
            $y = 170 - ((($weight - $min) / ($max - $min)) * 140);

            $points[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => $measurement->measured_at->format('d/m'),
                'weight' => $weight,
            ];
        }

        return [
            'points' => $points,
            'min' => round($min, 1),
            'max' => round($max, 1),
            'count' => $count,
        ];
    }

    private function ensureActivePlan(StudentAccount $account, Network $network): ?RedirectResponse
    {
        if ($account->student?->activeSubscription()) {
            return null;
        }

        return redirect()
            ->route('student.portal.plans', $network)
            ->withErrors(['plan' => 'Assine um plano para registrar suas medidas.']);
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
