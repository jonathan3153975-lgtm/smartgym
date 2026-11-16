@php
    $workoutNavItems = [
        [
            'href' => route('student.portal.workouts.index', $network).'#meus-treinos',
            'label' => 'Meus treinos',
            'icon' => 'fa-dumbbell',
            'active' => request()->routeIs('student.portal.workouts.index'),
        ],
        [
            'href' => route('student.portal.workouts.history', $network),
            'label' => 'Histórico',
            'icon' => 'fa-clock-rotate-left',
            'active' => request()->routeIs('student.portal.workouts.history*'),
        ],
        [
            'href' => route('student.portal.workouts.evolution', $network),
            'label' => 'Evolução',
            'icon' => 'fa-chart-line',
            'active' => request()->routeIs('student.portal.workouts.evolution'),
        ],
    ];
@endphp

<div class="-mx-4 sm:-mx-6">
    <nav class="sticky top-16 z-30 grid grid-cols-3 border-b border-custom bg-secondary" aria-label="Ações de treino">
        @foreach ($workoutNavItems as $item)
            <a href="{{ $item['href'] }}"
               class="flex items-center justify-center gap-1.5 {{ $loop->last ? '' : 'border-r border-custom' }} px-2 py-2.5 text-xs font-semibold transition-colors {{ $item['active'] ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                <i class="fas {{ $item['icon'] }}"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</div>
