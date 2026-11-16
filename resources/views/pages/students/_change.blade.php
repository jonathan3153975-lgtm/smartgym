@if ($changes[$field] ?? false)
    <span class="badge badge-warning ml-2">Alterado</span>
    @if ($previous !== null && $previous !== '')
        <p class="mt-1 text-xs text-text-secondary">Anterior: {{ $previous }}</p>
    @endif
@endif
