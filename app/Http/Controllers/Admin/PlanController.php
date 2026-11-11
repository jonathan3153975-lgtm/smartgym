<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Models\SaasPlanParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    /**
     * Display a listing of the SaaS plans.
     */
    public function index(Request $request): View|Response
    {
        $query = SaasPlan::query()
            ->withCount('parameters')
            ->withSum('parameters', 'value')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $plans = $query->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->view('pages.admin.plans._table', compact('plans'));
        }

        return view('pages.admin.plans.index', compact('plans'));
    }

    /**
     * Store a newly created SaaS plan.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validatePlan($request);

        $validated['slug'] = $this->resolveUniqueSlug($validated);

        $plan = SaasPlan::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Plano cadastrado com sucesso.',
                'data' => $plan,
            ]);
        }

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plano cadastrado com sucesso.');
    }

    /**
     * Return the plan and its parameters as JSON (used by the modals).
     */
    public function show(SaasPlan $plan): JsonResponse
    {
        return response()->json([
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'status' => $plan->status,
            'parameters' => $plan->parameters()
                ->get(['id', 'description', 'value'])
                ->map(fn (SaasPlanParameter $parameter) => [
                    'id' => $parameter->id,
                    'description' => $parameter->description,
                    'value' => $parameter->value,
                ]),
        ]);
    }

    /**
     * Update the specified SaaS plan.
     */
    public function update(Request $request, SaasPlan $plan): RedirectResponse|JsonResponse
    {
        $validated = $this->validatePlan($request, $plan);

        $validated['slug'] = $this->resolveUniqueSlug($validated, $plan);

        $plan->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Plano atualizado com sucesso.',
                'data' => $plan->fresh(),
            ]);
        }

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plano atualizado com sucesso.');
    }

    /**
     * Remove the specified SaaS plan.
     */
    public function destroy(SaasPlan $plan): RedirectResponse|JsonResponse
    {
        $plan->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Plano removido com sucesso.',
            ]);
        }

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plano removido com sucesso.');
    }

    /**
     * Validate the plan request data.
     *
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request, ?SaasPlan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('saas_plans', 'slug')->ignore($plan?->id),
            ],
        ]);
    }

    /**
     * Resolve a unique slug from the given data.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveUniqueSlug(array $data, ?SaasPlan $plan = null): string
    {
        $baseSlug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']);
        $baseSlug = $baseSlug ?: 'plano';
        $slug = $baseSlug;
        $suffix = 2;

        while (
            SaasPlan::query()
                ->where('slug', $slug)
                ->when($plan, fn ($query) => $query->whereKeyNot($plan->getKey()))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
