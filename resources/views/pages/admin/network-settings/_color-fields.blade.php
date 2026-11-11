@php
    $colorGroups = [
        'Estrutura e superfícies' => [
            'background_primary' => 'Fundo principal',
            'background_secondary' => 'Fundo secundário',
            'surface' => 'Superfície / cartões',
            'surface_elevated' => 'Superfície elevada',
            'overlay' => 'Sobreposição / modal',
            'border' => 'Bordas',
        ],
        'Textos' => [
            'text_primary' => 'Texto principal',
            'text_secondary' => 'Texto secundário',
        ],
        'Marca e ações' => [
            'primary' => 'Cor primária / botões',
            'on_primary' => 'Texto sobre botão primário',
            'secondary' => 'Cor secundária / botões',
            'on_secondary' => 'Texto sobre botão secundário',
            'accent' => 'Cor de destaque',
            'on_accent' => 'Texto sobre destaque',
        ],
        'Mensagens e estados' => [
            'success' => 'Sucesso',
            'on_success' => 'Texto sobre sucesso',
            'success_soft' => 'Fundo da mensagem de sucesso',
            'warning' => 'Atenção',
            'on_warning' => 'Texto sobre atenção',
            'warning_soft' => 'Fundo da mensagem de atenção',
            'danger' => 'Perigo / erro',
            'on_danger' => 'Texto sobre perigo',
            'danger_soft' => 'Fundo da mensagem de erro',
            'info' => 'Informação',
            'on_info' => 'Texto sobre informação',
            'info_soft' => 'Fundo da mensagem de informação',
        ],
    ];
@endphp

<div class="space-y-4">
    @foreach ($colorGroups as $groupLabel => $colorFields)
        <div class="space-y-2">
            <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-text-secondary">
                {{ $groupLabel }}
            </p>

            <div class="grid grid-cols-1 gap-2">
                @foreach ($colorFields as $key => $label)
                    @php($value = old("theme.{$mode}.{$key}", $palette[$key]))
                    <div class="flex items-center gap-3 rounded-lg border border-custom bg-bg-secondary px-3 py-2">
                        <input type="color"
                               id="theme_{{ $mode }}_{{ $key }}"
                               name="theme[{{ $mode }}][{{ $key }}]"
                               value="{{ $value }}"
                               data-theme-color
                               data-theme-color-mode="{{ $mode }}"
                               data-theme-color-key="{{ $key }}"
                               class="h-8 w-10 flex-shrink-0 cursor-pointer rounded-md border border-custom bg-transparent p-0.5"
                               aria-label="{{ $label }}">

                        <div class="min-w-0 flex-1">
                            <label for="theme_{{ $mode }}_{{ $key }}" class="block truncate text-sm font-medium">
                                {{ $label }}
                            </label>
                            <span class="font-mono text-xs uppercase text-text-secondary" data-color-hex>{{ $value }}</span>
                        </div>

                        @error("theme.{$mode}.{$key}")
                            <i class="fas fa-circle-exclamation text-red" title="{{ $message }}"></i>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
