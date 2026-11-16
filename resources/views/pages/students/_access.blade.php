@php($access = $access ?? null)

@if ($access && ! $access['is_empty'])
    <div class="mt-1 flex flex-wrap gap-1">
        @if ($access['has_active_plan'])
            <span class="badge badge-success" title="{{ collect($access['plans'])->pluck('name')->implode(', ') }}">
                Plano ativo
            </span>
        @endif

        @if ($access['day_pass'])
            <span class="badge badge-info">Diária hoje</span>
        @endif

        @foreach ($access['classes'] as $class)
            <span class="badge badge-info">Aula hoje: {{ $class }}</span>
        @endforeach

        @if ($access['trial_checkin'])
            <span class="badge badge-warning">Experimental hoje</span>
        @endif
    </div>
@endif
