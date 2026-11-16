@php
    $company = \App\Support\NetworkTheme::companyName($charge->network);
    $isReceipt = $document === 'recibo';
    $title = $isReceipt ? 'Recibo de pagamento' : 'Comprovante de pagamento';
    $number = ($isReceipt ? 'REC-' : 'CMP-').str_pad((string) $charge->id, 6, '0', STR_PAD_LEFT);
    $logo = \App\Support\NetworkTheme::logoUrl($charge->network);
    $paidAt = $charge->paid_at;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ $number }}</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #d8e1ee;
            --brand: #0d6efd;
            --soft: #f4f7fb;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--soft);
            color: var(--ink);
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 28px 18px 60px;
        }

        .sheet {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            margin: 0 auto;
            max-width: 760px;
            padding: 34px 38px;
        }

        .top {
            align-items: flex-start;
            border-bottom: 2px solid var(--ink);
            display: flex;
            gap: 18px;
            justify-content: space-between;
            padding-bottom: 18px;
        }

        .brand { display: flex; gap: 12px; align-items: center; }
        .brand img { height: 44px; width: 44px; object-fit: contain; }
        .brand-name { font-size: 18px; font-weight: 700; }
        .brand-sub { color: var(--muted); font-size: 12px; letter-spacing: .08em; text-transform: uppercase; }

        .doc-title { font-size: 22px; font-weight: 700; margin: 0; text-align: right; }
        .doc-number { color: var(--muted); font-size: 12px; margin-top: 4px; text-align: right; }

        .lead { font-size: 15px; line-height: 1.7; margin: 26px 0; }
        .lead strong { font-weight: 700; }

        .amount {
            background: var(--soft);
            border: 1px solid var(--line);
            border-radius: 12px;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 24px;
            padding: 16px 18px;
        }
        .amount span { color: var(--muted); font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .amount strong { font-size: 26px; }

        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid var(--line); font-size: 13px; padding: 10px 0; text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; width: 34%; }
        td { font-weight: 600; }

        .notes { color: var(--muted); font-size: 12px; line-height: 1.6; margin-top: 24px; }
        .signature { border-top: 1px solid var(--ink); margin-top: 54px; padding-top: 10px; text-align: center; }
        .signature p { margin: 0; }
        .signature .name { font-size: 13px; font-weight: 700; }
        .signature .role { color: var(--muted); font-size: 11px; }

        .toolbar {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 22px auto 0;
            max-width: 760px;
        }
        .toolbar button, .toolbar a {
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            padding: 11px 18px;
            text-decoration: none;
        }
        .toolbar button { background: var(--brand); border: 1px solid var(--brand); color: #fff; }
        .toolbar a { background: #fff; border: 1px solid var(--line); color: var(--ink); }

        .status-paid { color: #047857; }

        @media print {
            @page { margin: 12mm; }
            body { background: #fff; padding: 0; }
            .sheet { border: none; border-radius: 0; box-shadow: none; max-width: none; padding: 0; }
            .toolbar { display: none; }
            .notes { color: #555; }
        }
    </style>
</head>
<body>
    <main class="sheet">
        <div class="top">
            <div class="brand">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $company }}">
                @endif
                <div>
                    <div class="brand-name">{{ $company }}</div>
                    <div class="brand-sub">{{ $isReceipt ? 'Recibo' : 'Comprovante' }}</div>
                </div>
            </div>

            <div>
                <h1 class="doc-title">{{ $title }}</h1>
                <div class="doc-number">{{ $number }} · {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <p class="lead">
            @if ($isReceipt)
                Recebemos de <strong>{{ $charge->student?->name ?? 'Aluno não identificado' }}</strong>
                a importância de <strong>R$ {{ number_format((float) $charge->amount, 2, ',', '.') }}</strong>,
                referente a <strong>{{ $charge->description }}</strong>.
            @else
                Comprovamos o pagamento de <strong>R$ {{ number_format((float) $charge->amount, 2, ',', '.') }}</strong>
                realizado por <strong>{{ $charge->student?->name ?? 'Aluno não identificado' }}</strong>,
                referente a <strong>{{ $charge->description }}</strong>.
            @endif
        </p>

        <div class="amount">
            <span>Valor {{ $isReceipt ? 'recebido' : 'pago' }}</span>
            <strong>R$ {{ number_format((float) $charge->amount, 2, ',', '.') }}</strong>
        </div>

        <table>
            <tr>
                <th>Aluno</th>
                <td>{{ $charge->student?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th>Unidade</th>
                <td>{{ $charge->unit?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th>Produto / serviço</th>
                <td>{{ $charge->description }}</td>
            </tr>
            <tr>
                <th>Tipo</th>
                <td>{{ $charge->type_label }}</td>
            </tr>
            <tr>
                <th>Forma de pagamento</th>
                <td>{{ $methodLabel }}</td>
            </tr>
            <tr>
                <th>Data do pagamento</th>
                <td class="status-paid">{{ $paidAt?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            @if ($charge->sale?->code)
                <tr>
                    <th>Venda</th>
                    <td>{{ $charge->sale->code }}</td>
                </tr>
            @endif
            @if ($charge->due_date)
                <tr>
                    <th>Vencimento</th>
                    <td>{{ $charge->due_date->format('d/m/Y') }}</td>
                </tr>
            @endif
        </table>

        <p class="notes">
            {{ $isReceipt
                ? 'Este recibo confirma o recebimento do valor acima e é emitido para controle do aluno e da unidade.'
                : 'Este comprovante registra o pagamento da cobrança acima no sistema e não substitui o comprovante da operadora do cartão ou do banco.' }}
        </p>

        <div class="signature">
            <p class="name">{{ $company }}</p>
            <p class="role">Assinatura / carimbo da unidade</p>
        </div>
    </main>

    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir / salvar PDF</button>
        <a href="{{ route('management.cash.index') }}">Voltar ao caixa</a>
    </div>
</body>
</html>
