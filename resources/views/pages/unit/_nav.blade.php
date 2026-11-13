@php
    $unitTabs = [
        ['route' => 'unit.dashboard', 'label' => 'Visão geral', 'icon' => 'fa-chart-pie'],
        ['route' => 'unit.students.index', 'label' => 'Alunos', 'icon' => 'fa-user-graduate'],
        ['route' => 'unit.assessments.index', 'label' => 'Fichas', 'icon' => 'fa-clipboard-check'],
        ['route' => 'unit.payments.index', 'label' => 'Pagamentos', 'icon' => 'fa-credit-card'],
        ['route' => 'unit.access.index', 'label' => 'Acessos', 'icon' => 'fa-key'],
        ['route' => 'unit.classes.index', 'label' => 'Turmas e reservas', 'icon' => 'fa-calendar-check'],
    ];
@endphp

<div class="card space-y-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Administração da unidade</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $unit->name }}</h1>
            <p class="mt-1 text-sm text-text-secondary">
                {{ $unit->network?->name ?? 'Rede' }}
                @if ($unit->city)
                    &middot; {{ $unit->city }}/{{ $unit->state }}
                @endif
                &middot; {{ $unit->identifier }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($units->count() > 1)
                <form method="GET" action="{{ url()->current() }}">
                    <select name="unit_id" class="input-field" onchange="this.form.submit()" aria-label="Selecionar unidade">
                        @foreach ($units as $unitOption)
                            <option value="{{ $unitOption->id }}" @selected((int) $unitOption->id === (int) $unit->id)>
                                {{ $unitOption->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <span class="badge badge-success">Operação</span>
        </div>
    </div>

    <nav class="flex flex-wrap gap-1 border-t border-custom pt-3" aria-label="Menu da unidade">
        @foreach ($unitTabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs($tab['route']) || request()->routeIs(str_replace('.index', '.*', $tab['route'])) ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                <i class="fas {{ $tab['icon'] }}"></i>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
