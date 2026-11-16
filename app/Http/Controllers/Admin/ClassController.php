<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\Modality;
use App\Models\Person;
use App\Models\SchoolClass;
use App\Models\StudentNotification;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tenant = $this->tenant();

        $query = $tenant->scopeClasses(SchoolClass::query())
            ->with(['unit', 'modality', 'schedules.person', 'schedules.teacher'])
            ->withCount('schedules')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('unit', fn ($unit) => $unit->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('modality', fn ($modality) => $modality->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->integer('unit_id'));
        }

        $classes = $query->paginate(15)->withQueryString();
        $units = $tenant->unitsQuery()->orderBy('name')->get(['id', 'name']);
        $teachers = $this->teacherOptions($units->pluck('id')->all());
        $modalities = $this->modalityOptions($units->pluck('id')->all());

        if ($request->ajax()) {
            return response()->view('pages.admin.classes._table', compact('classes'));
        }

        return view('pages.admin.classes.index', compact('classes', 'units', 'teachers', 'modalities'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $validated = $this->validateClass($request, $tenant);
        $validated['network_id'] = $tenant->networkId();
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $class = SchoolClass::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Turma cadastrada com sucesso.',
                'data' => $class,
            ]);
        }

        return redirect()->route('admin.classes.index')->with('success', 'Turma cadastrada com sucesso.');
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        $validated = $this->validateClass($request, $tenant);
        $validated['updated_by'] = auth()->id();
        unset($validated['network_id']);

        $class->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Turma atualizada com sucesso.',
                'data' => $class->fresh(),
            ]);
        }

        return redirect()->route('admin.classes.index')->with('success', 'Turma atualizada com sucesso.');
    }

    public function destroy(SchoolClass $class): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        $class->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Turma removida com sucesso.',
            ]);
        }

        return redirect()->route('admin.classes.index')->with('success', 'Turma removida com sucesso.');
    }

    public function editData(SchoolClass $class): JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        return response()->json([
            'id' => $class->id,
            'unit_id' => $class->unit_id,
            'modality_id' => $class->modality_id,
            'name' => $class->name,
            'drop_in_price' => $class->drop_in_price,
            'status' => $class->status,
        ]);
    }

    public function schedulesData(SchoolClass $class): JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        $class->load(['schedules.person', 'schedules.teacher']);
        $unit = $class->unit;
        $teachers = $unit ? $this->teacherOptions([$unit->id]) : collect();

        return response()->json([
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'unit_id' => $class->unit_id,
                'unit_name' => $unit?->name,
            ],
            'schedules' => $class->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'weekday' => $schedule->weekday,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => $schedule->end_time ? substr((string) $schedule->end_time, 0, 5) : null,
                'person_id' => $schedule->person_id,
                'vacancies' => $schedule->vacancies,
            ])->values(),
            'teachers' => $teachers->values(),
        ]);
    }

    public function syncSchedules(Request $request, SchoolClass $class): JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        $validated = $request->validate([
            'schedules' => ['nullable', 'array'],
            'schedules.*.id' => ['nullable', 'integer', 'exists:class_schedules,id'],
            'schedules.*.weekday' => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.person_id' => ['nullable', 'integer', 'exists:people,id'],
            'schedules.*.vacancies' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $schedules = collect($validated['schedules'] ?? []);
        $keptIds = [];
        $seen = [];

        foreach ($schedules as $index => $data) {
            if (! empty($data['end_time']) && $data['end_time'] <= $data['start_time']) {
                throw ValidationException::withMessages([
                    "schedules.{$index}.end_time" => 'O horário final deve ser posterior ao inicial.',
                ]);
            }

            $signature = $data['weekday'].'-'.$data['start_time'];

            if (isset($seen[$signature])) {
                throw ValidationException::withMessages([
                    "schedules.{$index}.start_time" => 'Já existe um horário com este dia e início.',
                ]);
            }

            $seen[$signature] = true;

            if (! empty($data['person_id'])) {
                $teacherLinked = Person::query()
                    ->whereKey($data['person_id'])
                    ->where(fn ($query) => $query
                        ->where('unit_id', $class->unit_id)
                        ->orWhereNull('unit_id'))
                    ->where('category', 'staff')
                    ->whereIn('job_function', [Person::FUNCTION_INSTRUCTOR, Person::FUNCTION_PERSONAL_TRAINER])
                    ->where('status', 'active')
                    ->exists();

                if (! $teacherLinked) {
                    throw ValidationException::withMessages([
                        "schedules.{$index}.person_id" => 'O professor deve ser uma pessoa da equipe com função de professor, instrutor, treinador ou personal-trainer.',
                    ]);
                }
            }

            $schedule = ! empty($data['id'])
                ? $class->schedules()->whereKey($data['id'])->first()
                : null;

            if (! $schedule) {
                $schedule = new ClassSchedule(['class_id' => $class->id]);
            }

            $schedule->fill([
                'person_id' => $data['person_id'] ?? null,
                'weekday' => $data['weekday'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'vacancies' => $data['vacancies'],
            ])->save();

            $keptIds[] = $schedule->id;
        }

        $removedSchedules = $class->schedules()->whereNotIn('id', $keptIds)->get();

        foreach ($removedSchedules as $removedSchedule) {
            $futureBookings = ClassBooking::query()
                ->where('class_schedule_id', $removedSchedule->id)
                ->whereDate('booked_for', '>=', now()->toDateString())
                ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
                ->get();

            foreach ($futureBookings as $booking) {
                $isPaidSingle = $booking->type === ClassBooking::TYPE_SINGLE
                    && (float) $booking->amount > 0
                    && $booking->paid_at !== null;

                StudentNotification::create([
                    'network_id' => $booking->network_id,
                    'student_id' => $booking->student_id,
                    'title' => 'Horário indisponível',
                    'message' => $isPaidSingle
                        ? 'O horário '.$removedSchedule->weekday_label.' '.$removedSchedule->time_label.' da turma '.$class->name.' não está mais disponível. Você recebeu um crédito para reservar outra aula desta turma.'
                        : 'O horário '.$removedSchedule->weekday_label.' '.$removedSchedule->time_label.' da turma '.$class->name.' não está mais disponível. Escolha outro horário.',
                    'type' => $isPaidSingle ? 'class_credit' : 'schedule_canceled',
                ]);

                $booking->update([
                    'status' => ClassBooking::STATUS_CANCELED,
                    'credit_available' => $isPaidSingle ? true : $booking->credit_available,
                    'credit_granted_at' => $isPaidSingle ? now() : $booking->credit_granted_at,
                ]);
            }

            $removedSchedule->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Grade de horários atualizada com sucesso.',
            'count' => count($keptIds),
        ]);
    }

    /**
     * Modalities linked to the accessible units, used to populate the modality select.
     *
     * @param  array<int, int>  $unitIds
     * @return Collection<int, array{id: int, name: string, unit_ids: array<int, int>}>
     */
    private function modalityOptions(array $unitIds)
    {
        return Modality::query()
            ->where('network_id', TenantContext::current()->networkId())
            ->whereHas('units', fn ($query) => $query->whereIn('units.id', $unitIds))
            ->with(['units' => fn ($query) => $query->whereIn('units.id', $unitIds)])
            ->orderBy('name')
            ->get()
            ->map(fn (Modality $modality) => [
                'id' => (int) $modality->id,
                'name' => $modality->name,
                'unit_ids' => $modality->units->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ]);
    }

    /**
     * People registered as staff with a teaching function in the accessible units.
     *
     * @param  array<int, int>  $unitIds
     * @return Collection<int, array{id: int, name: string, unit_id: int}>
     */
    private function teacherOptions(array $unitIds)
    {
        return Person::query()
            ->where('network_id', TenantContext::current()->networkId())
            ->where(fn ($query) => $query
                ->whereIn('unit_id', $unitIds)
                ->orWhereNull('unit_id'))
            ->where('category', 'staff')
            ->whereIn('job_function', [Person::FUNCTION_INSTRUCTOR, Person::FUNCTION_PERSONAL_TRAINER])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'unit_id'])
            ->map(fn (Person $person) => [
                'id' => (int) $person->id,
                'name' => $person->name,
                'unit_id' => (int) $person->unit_id,
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClass(Request $request, TenantContext $tenant): array
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'modality_id' => ['nullable', 'integer', 'exists:modalities,id'],
            'name' => ['required', 'string', 'max:255'],
            'drop_in_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $accessibleUnits = $tenant->unitsQuery()->pluck('id')->map(fn ($id) => (int) $id);

        if (! $accessibleUnits->contains((int) $validated['unit_id'])) {
            throw ValidationException::withMessages([
                'unit_id' => 'Selecione uma unidade que você pode acessar.',
            ]);
        }

        if (! empty($validated['modality_id'])) {
            $modalityLinked = Modality::query()
                ->whereKey($validated['modality_id'])
                ->where('network_id', $tenant->networkId())
                ->whereHas('units', fn ($query) => $query->whereKey($validated['unit_id']))
                ->exists();

            if (! $modalityLinked) {
                throw ValidationException::withMessages([
                    'modality_id' => 'A modalidade selecionada não está disponível na unidade da turma.',
                ]);
            }
        }

        return $validated;
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar turmas.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar turmas.');

        return $tenant;
    }
}
