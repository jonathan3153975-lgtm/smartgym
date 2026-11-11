@php
    $flashTypes = [
        'success' => ['icon' => 'success', 'color' => 'var(--color-green)', 'bg' => 'rgba(0, 230, 118, 0.1)', 'border' => 'var(--color-green)'],
        'error' => ['icon' => 'error', 'color' => 'var(--color-red)', 'bg' => 'rgba(255, 56, 100, 0.1)', 'border' => 'var(--color-red)'],
        'warning' => ['icon' => 'warning', 'color' => 'var(--color-yellow)', 'bg' => 'rgba(255, 179, 0, 0.1)', 'border' => 'var(--color-yellow)'],
        'info' => ['icon' => 'info', 'color' => 'var(--color-cyan-neon)', 'bg' => 'rgba(0, 229, 255, 0.1)', 'border' => 'var(--color-cyan-neon)'],
    ];
@endphp

@foreach ($flashTypes as $type => $config)
    @if (session()->has($type))
        <div class="fixed top-20 right-4 z-50 max-w-sm animate-slide-in"
             style="background: {{ $config['bg'] }}; border: 1px solid {{ $config['border'] }}; border-radius: 12px; padding: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.3);"
             role="alert"
             aria-live="polite">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                     style="background: {{ $config['color'] }}; color: var(--color-bg-primary);">
                    @if ($type === 'success')
                        <i class="fas fa-check text-sm"></i>
                    @elseif ($type === 'error')
                        <i class="fas fa-times text-sm"></i>
                    @elseif ($type === 'warning')
                        <i class="fas fa-exclamation-triangle text-sm"></i>
                    @else
                        <i class="fas fa-info-circle text-sm"></i>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium" style="color: var(--color-text-primary);">{{ session($type) }}</p>
                </div>
                <button type="button"
                        class="flex-shrink-0 p-1 rounded hover:bg-black/10 transition-colors"
                        style="color: var(--color-text-secondary);"
                        onclick="this.parentElement.parentElement.remove()"
                        aria-label="Fechar">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>

        <script>
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('[role="alert"]');
                alerts.forEach(alert => {
                    if (alert.textContent.includes('{{ addslashes(session($type)) }}')) {
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateX(100%)';
                        setTimeout(() => alert.remove(), 300);
                    }
                });
            }, 5000);
        </script>
    @endif
@endforeach

@if (session()->has('validation_errors'))
    <div class="fixed top-20 right-4 z-50 max-w-sm animate-slide-in"
         style="background: rgba(255, 56, 100, 0.1); border: 1px solid var(--color-red); border-radius: 12px; padding: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.3);"
         role="alert"
         aria-live="polite">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--color-red); color: var(--color-bg-primary);">
                <i class="fas fa-exclamation-circle text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium" style="color: var(--color-text-primary);">Erro de validação</p>
                <ul class="mt-2 space-y-1 text-sm" style="color: var(--color-text-secondary);">
                    @foreach (session('validation_errors') as $errors)
                        @foreach ($errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
            <button type="button"
                    class="flex-shrink-0 p-1 rounded hover:bg-black/10 transition-colors"
                    style="color: var(--color-text-secondary);"
                    onclick="this.parentElement.parentElement.remove()"
                    aria-label="Fechar">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    </div>
@endif

<style>
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    .animate-slide-in {
        animation: slideIn 0.3s ease-out;
    }
</style>