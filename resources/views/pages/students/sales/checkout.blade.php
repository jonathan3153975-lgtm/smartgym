@extends('layouts.app')

@section('title', 'Finalizar venda - '.$sale->code)

@section('content')
<div class="container mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.sales.create', $student) }}" class="text-sm text-text-secondary hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para a venda
            </a>
            <h1 class="mt-1 text-2xl font-bold">Finalizar venda</h1>
            <p class="mt-1 text-text-secondary">
                {{ $sale->code }} &middot; {{ $student->name }} &middot; {{ $sale->unit?->name }}
            </p>
        </div>
        <span class="badge badge-warning self-start sm:self-auto">Aguardando pagamento</span>
    </div>

    <div class="space-y-6">
        <form id="finalizeForm" class="card space-y-4" data-total="{{ (float) $sale->total }}">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-credit-card text-cyan"></i>
                    Formas de pagamento
                </h2>
                <span class="badge badge-info">Total {{ 'R$ '.number_format((float) $sale->total, 2, ',', '.') }}</span>
            </div>

            <div id="paymentRows" class="space-y-3"></div>

            <button type="button" id="addPaymentRow" class="btn-outline w-full">
                <i class="fas fa-plus mr-2"></i>
                Adicionar forma de pagamento
            </button>

            <div class="flex items-center justify-between rounded-lg border border-custom bg-bg-secondary px-3 py-2 text-sm">
                <span class="text-text-secondary">Valor restante</span>
                <strong id="paymentRemaining">R$ 0,00</strong>
            </div>

            <p id="paymentError" class="hidden rounded-lg px-3 py-2 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <button type="submit" id="confirmSale" class="btn-primary w-full">
                <i class="fas fa-check mr-2"></i>
                Confirmar venda
            </button>

            <a href="{{ route('students.contracts', $student) }}" class="btn-outline w-full text-center">
                Cancelar
            </a>
        </form>

        <section class="card space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-file-invoice text-cyan"></i>
                        Extrato dos itens da venda
                    </h2>
                    <p class="mt-1 text-xs text-text-secondary">
                        Excluir um item recalcula o total a pagar desta venda.
                    </p>
                </div>
                <span class="badge badge-secondary">{{ $sale->items->count() }} item(ns)</span>
            </div>

            <div class="space-y-2">
                @foreach ($sale->items as $item)
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-custom bg-bg-secondary px-3 py-2">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge badge-info">{{ $item->type_label }}</span>
                                @if ($item->type === 'plan' && $item->auto_renew)
                                    <span class="badge badge-success">Renovação automática</span>
                                @endif
                                <span class="text-xs text-text-secondary">{{ $item->starts_at?->format('d/m/Y') }} a {{ $item->ends_at?->format('d/m/Y') }}</span>
                            </div>
                            <p class="mt-1 text-sm font-medium">{{ $item->description }}</p>
                            @if ($item->schedule)
                                <p class="text-xs text-text-secondary">
                                    {{ $item->schedule->weekday_label }} · {{ $item->schedule->time_label }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="whitespace-nowrap font-semibold">R$ {{ number_format((float) $item->amount, 2, ',', '.') }}</span>

                            @if (! in_array($sale->status, ['paid', 'canceled'], true))
                                <form method="POST"
                                      action="{{ route('students.sales.items.destroy', [$student, $sale, $item]) }}"
                                      onsubmit="return confirm('Excluir este item da venda? O total será recalculado.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-text-secondary transition-colors hover:text-red"
                                            title="Excluir item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between border-t border-custom pt-3 text-lg">
                <span class="font-medium">Total</span>
                <span class="font-bold text-cyan">R$ {{ number_format((float) $sale->total, 2, ',', '.') }}</span>
            </div>
        </section>
    </div>
</div>

<script>
    (function () {
        const methods = @json($methods, JSON_UNESCAPED_UNICODE);
        const total = Number(document.getElementById('finalizeForm').dataset.total || 0);
        const rows = document.getElementById('paymentRows');
        const addButton = document.getElementById('addPaymentRow');
        const remainingLabel = document.getElementById('paymentRemaining');
        const form = document.getElementById('finalizeForm');
        const confirmButton = document.getElementById('confirmSale');

        function money(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
        }

        function addRow(amount = '') {
            const row = document.createElement('div');
            row.className = 'rounded-xl border border-custom bg-bg-secondary p-3 space-y-2';
            row.innerHTML = `
                <div class="flex items-center justify-between">
                    <label class="text-xs font-semibold uppercase tracking-wider text-text-secondary">Forma de pagamento</label>
                    <button type="button" class="remove-payment p-1 text-red" title="Remover">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <select data-payment="method" class="input-field">
                    ${Object.entries(methods).map(([value, label]) => `<option value="${value}">${label}</option>`).join('')}
                </select>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-text-secondary">Valor</label>
                        <input type="number" step="0.01" min="0.01" data-payment="amount" value="${amount}" class="input-field">
                    </div>
                    <div class="installments-wrap hidden">
                        <label class="text-xs text-text-secondary">Parcelas</label>
                        <select data-payment="installments" class="input-field">
                            ${Array.from({ length: 12 }, (_, index) => `<option value="${index + 1}">${index + 1}x</option>`).join('')}
                        </select>
                    </div>
                </div>
            `;

            rows.appendChild(row);
            updateInstallments(row);
            updateRemaining();
        }

        function updateInstallments(row) {
            const method = row.querySelector('[data-payment="method"]').value;
            row.querySelector('.installments-wrap').classList.toggle('hidden', method !== 'credit_card');
        }

        function updateRemaining() {
            const paid = Array.from(rows.querySelectorAll('[data-payment="amount"]'))
                .reduce((sum, input) => sum + Number(input.value || 0), 0);

            remainingLabel.textContent = money(Math.max(0, total - paid));
        }

        rows.addEventListener('change', function (event) {
            const row = event.target.closest('.rounded-xl');

            if (event.target.matches('[data-payment="method"]') && row) {
                updateInstallments(row);
            }

            if (event.target.matches('[data-payment="amount"]')) {
                updateRemaining();
            }
        });

        rows.addEventListener('input', function (event) {
            if (event.target.matches('[data-payment="amount"]')) {
                updateRemaining();
            }
        });

        rows.addEventListener('click', function (event) {
            if (event.target.closest('.remove-payment')) {
                event.target.closest('.rounded-xl').remove();
                updateRemaining();
            }
        });

        addButton.addEventListener('click', function () {
            addRow('');
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            confirmButton.disabled = true;

            const payments = Array.from(rows.querySelectorAll('.rounded-xl')).map((row) => ({
                method: row.querySelector('[data-payment="method"]').value,
                amount: Number(row.querySelector('[data-payment="amount"]').value || 0),
                installments: Number(row.querySelector('[data-payment="installments"]').value || 1),
            }));

            const paid = payments.reduce((sum, payment) => sum + payment.amount, 0);

            if (payments.length === 0 || paid <= 0) {
                alertError('Pagamento', 'Adicione ao menos uma forma de pagamento.');
                confirmButton.disabled = false;
                return;
            }

            if (Math.abs(paid - total) > 0.01) {
                alertError('Pagamento', 'A soma das formas de pagamento deve ser igual ao total da venda.');
                confirmButton.disabled = false;
                return;
            }

            try {
                const response = await fetch('{{ route('students.sales.finalize', [$student, $sale]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.SmartGym.csrfToken,
                    },
                    body: JSON.stringify({ payments }),
                });

                const data = await response.json();

                if (! response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                    alertError('Não foi possível finalizar', errors || data.message || 'Tente novamente.');
                    return;
                }

                await alertSuccess('Venda finalizada!', data.message);
                window.location = data.redirect;
            } catch (error) {
                alertError('Erro', 'Não foi possível finalizar a venda. Tente novamente.');
            } finally {
                confirmButton.disabled = false;
            }
        });

        addRow(total.toFixed(2));
        updateRemaining();
    })();
</script>
@endsection
