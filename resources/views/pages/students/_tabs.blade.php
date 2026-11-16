@php
    $activeTab = $activeTab ?? 'overview';

    $tabs = [
        [
            'key' => 'overview',
            'label' => 'Visão geral',
            'icon' => 'fa-gauge-high',
            'url' => route('students.show', $student),
            'permission' => 'students.view',
        ],
        [
            'key' => 'cadastro',
            'label' => 'Cadastro',
            'icon' => 'fa-id-card',
            'url' => route('students.show', ['student' => $student, 'tab' => 'cadastro']),
            'permission' => 'students.view',
        ],
        [
            'key' => 'technical',
            'label' => 'Ficha técnica',
            'icon' => 'fa-chart-line',
            'url' => route('students.technical-sheet', $student),
            'permission' => 'students.view',
        ],
        [
            'key' => 'workouts',
            'label' => 'Treinos',
            'icon' => 'fa-dumbbell',
            'url' => route('students.workouts', $student),
            'permission' => 'students.view',
        ],
        [
            'key' => 'financial',
            'label' => 'Financeiro',
            'icon' => 'fa-file-invoice-dollar',
            'url' => route('students.contracts', $student),
            'permission' => 'students.view',
        ],
    ];
@endphp

<div class="border-b border-custom">
    <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Seções do aluno">
        @foreach ($tabs as $tab)
            @continue(isset($tab['permission']) && ! auth()->user()?->can($tab['permission']))

            <a href="{{ $tab['url'] }}"
               class="flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition-colors {{ $activeTab === $tab['key'] ? 'border-cyan text-cyan' : 'border-transparent text-text-secondary hover:border-custom hover:text-text-primary' }}">
                <i class="fas {{ $tab['icon'] }}"></i>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
