<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Models\SaasPlanParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanParameterController extends Controller
{
    /**
     * Store a new parameter for the plan.
     */
    public function store(Request $request, SaasPlan $plan): JsonResponse
    {
        $validated = $this->validateParameter($request);

        $parameter = $plan->parameters()->create([
            'description' => $validated['description'],
            'value' => $this->parseMoney($validated['value']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Parâmetro cadastrado com sucesso.',
            'data' => $this->presentParameter($parameter),
        ]);
    }

    /**
     * Update a parameter of the plan.
     */
    public function update(Request $request, SaasPlan $plan, SaasPlanParameter $parameter): JsonResponse
    {
        $this->ensureParameterBelongsToPlan($plan, $parameter);

        $validated = $this->validateParameter($request);

        $parameter->update([
            'description' => $validated['description'],
            'value' => $this->parseMoney($validated['value']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Parâmetro atualizado com sucesso.',
            'data' => $this->presentParameter($parameter->fresh()),
        ]);
    }

    /**
     * Remove a parameter from the plan.
     */
    public function destroy(SaasPlan $plan, SaasPlanParameter $parameter): JsonResponse
    {
        $this->ensureParameterBelongsToPlan($plan, $parameter);

        $parameter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parâmetro removido com sucesso.',
        ]);
    }

    /**
     * Validate the parameter request data.
     *
     * @return array<string, mixed>
     */
    private function validateParameter(Request $request): array
    {
        return $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'value' => ['required'],
        ]);
    }

    /**
     * Abort when the parameter does not belong to the given plan.
     */
    private function ensureParameterBelongsToPlan(SaasPlan $plan, SaasPlanParameter $parameter): void
    {
        abort_unless($parameter->saas_plan_id === $plan->id, 404);
    }

    /**
     * Convert a formatted money value (e.g. "1.000,00") to a float.
     */
    private function parseMoney(mixed $value): float
    {
        $raw = preg_replace('/[^\d,.-]/', '', (string) $value) ?? '';

        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        return round((float) $raw, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentParameter(SaasPlanParameter $parameter): array
    {
        return [
            'id' => $parameter->id,
            'saas_plan_id' => $parameter->saas_plan_id,
            'description' => $parameter->description,
            'value' => $parameter->value,
        ];
    }
}
