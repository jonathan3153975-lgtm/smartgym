@php
    $colorFields = [
        'background_primary' => 'Fundo principal',
        'background_secondary' => 'Fundo secundário',
        'surface' => 'Superfície / cartões',
        'surface_elevated' => 'Superfície elevada',
        'border' => 'Bordas',
        'text_primary' => 'Texto principal',
        'text_secondary' => 'Texto secundário',
        'primary' => 'Cor primária / botões',
        'on_primary' => 'Texto sobre botão primário',
        'secondary' => 'Cor secundária / botões',
        'on_secondary' => 'Texto sobre botão secundário',
        'accent' => 'Cor de destaque',
        'on_accent' => 'Texto sobre destaque',
        'success' => 'Sucesso',
        'warning' => 'Alerta',
        'danger' => 'Perigo',
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    @foreach ($colorFields as $key => $label)
        @php($value = old("theme.{$mode}.{$key}", $palette[$key]))
        <div class="rounded-lg border border-custom bg-bg-secondary p-3">
            <label for="theme_{{ $mode }}_{{ $key }}" class="label-field text-sm mb-2">{{ $label }}</label>
            <div class="flex items-center gap-3">
                <input type="color"
                       id="theme_{{ $mode }}_{{ $key }}"
                       name="theme[{{ $mode }}][{{ $key }}]"
                       value="{{ $value }}"
                       class="h-10 w-14 rounded-lg border border-custom bg-transparent p-1 cursor-pointer">
                <span class="text-xs font-mono text-text-secondary" data-color-hex>{{ $value }}</span>
            </div>
            @error("theme.{$mode}.{$key}")
                <p class="mt-1 text-xs text-red">{{ $message }}</p>
            @enderror
        </div>
    @endforeach
</div>
