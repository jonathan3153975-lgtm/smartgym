@php
    $palette = $palette ?? \App\Support\NetworkTheme::DEFAULTS['dark'];

    $alerts = [
        ['key' => 'success', 'label' => 'Sucesso', 'icon' => 'fa-check', 'text' => 'Aluno cadastrado com sucesso.'],
        ['key' => 'info', 'label' => 'Informação', 'icon' => 'fa-circle-info', 'text' => 'Novidade disponível para a rede.'],
        ['key' => 'warning', 'label' => 'Atenção', 'icon' => 'fa-triangle-exclamation', 'text' => 'Revise os dados antes de salvar.'],
        ['key' => 'danger', 'label' => 'Erro', 'icon' => 'fa-circle-exclamation', 'text' => 'Não foi possível concluir a ação.'],
    ];
@endphp

<div data-theme-preview
     data-palette="{{ $mode }}"
     class="space-y-3 rounded-xl border p-4"
     style="
        background: var(--tp-background-primary);
        border-color: var(--tp-border);
        color: var(--tp-text-primary);
        @foreach ($palette as $key => $value)
            --tp-{{ str_replace('_', '-', $key) }}: {{ $value }};
        @endforeach
     ">
    <style>
        [data-theme-preview] .tp-surface {
            background: var(--tp-surface);
            border-color: var(--tp-border);
        }

        [data-theme-preview] .tp-elevated {
            background: var(--tp-surface-elevated);
            border-color: var(--tp-border);
        }

        [data-theme-preview] .tp-muted {
            color: var(--tp-text-secondary);
        }

        [data-theme-preview] .tp-btn {
            border-radius: 0.45rem;
            padding: 0.35rem 0.65rem;
            font-size: 0.68rem;
            font-weight: 600;
            line-height: 1;
            border: 1px solid transparent;
            cursor: default;
        }

        [data-theme-preview] .tp-input {
            background: var(--tp-background-secondary);
            border: 1px solid var(--tp-border);
            color: var(--tp-text-primary);
            border-radius: 0.45rem;
            padding: 0.35rem 0.55rem;
            font-size: 0.68rem;
        }

        [data-theme-preview] .tp-table th {
            background: var(--tp-background-secondary);
            color: var(--tp-text-secondary);
            border-color: var(--tp-border);
        }

        [data-theme-preview] .tp-table td {
            border-color: var(--tp-border);
            color: var(--tp-text-primary);
        }
    </style>

    <div class="flex items-center justify-between gap-2">
        <span class="tp-muted text-[0.65rem] font-semibold uppercase tracking-wider">
            Prévia dos componentes
        </span>
        <span class="tp-elevated tp-muted rounded-full border px-2 py-0.5 text-[0.65rem] font-medium">
            Tema <span data-theme-preview-label>{{ $mode === 'dark' ? 'escuro' : 'claro' }}</span>
        </span>
    </div>

    {{-- Mensagens --}}
    <div class="space-y-1.5">
        <p class="tp-muted text-[0.6rem] font-semibold uppercase tracking-wider">Mensagens</p>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            @foreach ($alerts as $alert)
                <div class="flex items-start gap-2 rounded-lg border px-2.5 py-2"
                     style="background: var(--tp-{{ $alert['key'] }}-soft); border-color: var(--tp-{{ $alert['key'] }});">
                    <span class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full"
                          style="background: var(--tp-{{ $alert['key'] }}); color: var(--tp-on-{{ $alert['key'] }});">
                        <i class="fas {{ $alert['icon'] }} text-[0.5rem]"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[0.7rem] font-semibold">{{ $alert['label'] }}</p>
                        <p class="tp-muted text-[0.62rem] leading-tight">{{ $alert['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Card --}}
    <div class="tp-surface rounded-lg border p-3">
        <div class="flex items-start justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-[0.65rem] font-bold"
                      style="background: var(--tp-primary); color: var(--tp-on-primary);">JS</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">Card de exemplo</p>
                    <p class="tp-muted truncate text-[0.65rem]">Superfície, borda e textos do tema.</p>
                </div>
            </div>
            <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-[0.6rem] font-semibold"
                  style="background: var(--tp-success); color: var(--tp-on-success);">
                Ativo
            </span>
        </div>

        <div class="mt-3 flex flex-wrap gap-1.5">
            <button type="button" class="tp-btn" style="background: var(--tp-primary); color: var(--tp-on-primary);">
                Primário
            </button>
            <button type="button" class="tp-btn" style="background: var(--tp-secondary); color: var(--tp-on-secondary);">
                Secundário
            </button>
            <button type="button" class="tp-btn" style="background: var(--tp-accent); color: var(--tp-on-accent);">
                Destaque
            </button>
            <button type="button" class="tp-btn" style="background: transparent; border-color: var(--tp-primary); color: var(--tp-primary);">
                Contorno
            </button>
            <button type="button" class="tp-btn" style="background: var(--tp-danger); color: var(--tp-on-danger);">
                Excluir
            </button>
        </div>
    </div>

    {{-- Badges --}}
    <div class="flex flex-wrap gap-1.5">
        <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold"
              style="background: var(--tp-success-soft); color: var(--tp-success);">Sucesso</span>
        <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold"
              style="background: var(--tp-warning-soft); color: var(--tp-warning);">Atenção</span>
        <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold"
              style="background: var(--tp-danger-soft); color: var(--tp-danger);">Erro</span>
        <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold"
              style="background: var(--tp-info-soft); color: var(--tp-info);">Informação</span>
        <span class="tp-elevated tp-muted rounded-full border px-2 py-0.5 text-[0.62rem] font-medium">Neutro</span>
    </div>

    {{-- Formulário --}}
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div>
            <p class="tp-muted mb-1 text-[0.6rem] font-medium">Campo de texto</p>
            <div class="tp-input">Digite algo...</div>
        </div>
        <div>
            <p class="tp-muted mb-1 text-[0.6rem] font-medium">Seleção</p>
            <div class="tp-input flex items-center justify-between">
                <span>Selecione...</span>
                <i class="fas fa-chevron-down text-[0.55rem]"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <label class="flex items-center gap-1.5 text-[0.65rem]">
            <input type="checkbox" checked class="h-3 w-3" style="accent-color: var(--tp-primary);">
            Opção marcada
        </label>
        <label class="flex items-center gap-1.5 text-[0.65rem]">
            <input type="radio" checked class="h-3 w-3" style="accent-color: var(--tp-secondary);">
            Alternativa
        </label>
        <span class="flex items-center gap-1.5 text-[0.65rem]">
            <span class="relative inline-flex h-3.5 w-6 items-center rounded-full"
                  style="background: var(--tp-primary);">
                <span class="absolute right-0.5 h-2.5 w-2.5 rounded-full" style="background: var(--tp-on-primary);"></span>
            </span>
            Ativo
        </span>
    </div>

    {{-- Tabela --}}
    <div class="overflow-hidden rounded-lg border" style="border-color: var(--tp-border);">
        <table class="tp-table w-full text-left text-[0.62rem]">
            <thead>
                <tr>
                    <th class="px-2 py-1.5 font-semibold">Aluno</th>
                    <th class="px-2 py-1.5 font-semibold">Unidade</th>
                    <th class="px-2 py-1.5 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border-t px-2 py-1.5">Maria Souza</td>
                    <td class="border-t px-2 py-1.5">Matriz</td>
                    <td class="border-t px-2 py-1.5" style="color: var(--tp-success);">Ativo</td>
                </tr>
                <tr>
                    <td class="border-t px-2 py-1.5">João Lima</td>
                    <td class="border-t px-2 py-1.5">Centro</td>
                    <td class="border-t px-2 py-1.5" style="color: var(--tp-warning);">Pendente</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Abas e paginação --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex gap-1">
            <span class="rounded-md border px-2 py-1 text-[0.62rem] font-medium"
                  style="background: var(--tp-surface); border-color: var(--tp-primary); color: var(--tp-primary);">Ativa</span>
            <span class="tp-muted rounded-md border px-2 py-1 text-[0.62rem] font-medium"
                  style="border-color: var(--tp-border);">Inativa</span>
        </div>
        <div class="flex items-center gap-1">
            <span class="tp-elevated tp-muted rounded-md border px-1.5 py-0.5 text-[0.6rem]">&lsaquo;</span>
            <span class="rounded-md px-1.5 py-0.5 text-[0.6rem] font-semibold"
                  style="background: var(--tp-primary); color: var(--tp-on-primary);">1</span>
            <span class="tp-muted rounded-md border px-1.5 py-0.5 text-[0.6rem]"
                  style="border-color: var(--tp-border);">2</span>
            <span class="tp-elevated tp-muted rounded-md border px-1.5 py-0.5 text-[0.6rem]">&rsaquo;</span>
        </div>
    </div>

    {{-- Sobreposição / modal --}}
    <div class="relative h-20 overflow-hidden rounded-lg border" style="border-color: var(--tp-border); background: var(--tp-surface-elevated);">
        <div class="absolute inset-0" style="background: color-mix(in srgb, var(--tp-overlay) 82%, transparent);"></div>
        <div class="tp-elevated absolute left-1/2 top-1/2 w-2/3 -translate-x-1/2 -translate-y-1/2 rounded-md border p-2">
            <p class="text-[0.65rem] font-semibold">Modal / dropdown</p>
            <p class="tp-muted text-[0.58rem]">Sobreposição e superfície elevada.</p>
        </div>
    </div>
</div>
