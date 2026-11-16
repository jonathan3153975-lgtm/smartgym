<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Cobrança pendente</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;border:1px solid #dbe3ef;">
        <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#0d6efd;">
            {{ config('app.name') }}
        </p>
        <h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;">Você possui uma cobrança pendente</h1>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Olá, {{ $student->name }}. Existe uma cobrança pendente no valor de
            <strong>R$ {{ number_format((float) $charge->amount, 2, ',', '.') }}</strong>.
        </p>

        <div style="background:#f1f5f9;border-radius:10px;padding:16px;margin-bottom:20px;">
            <p style="margin:0 0 6px;font-size:13px;color:#475569;">Descrição</p>
            <p style="margin:0 0 10px;font-size:16px;font-weight:700;">{{ $charge->description }}</p>
            <p style="margin:0;font-size:13px;color:#475569;">
                Tipo: {{ $charge->type_label }}
                @if ($charge->due_date)
                    &middot; Vencimento: {{ $charge->due_date->format('d/m/Y') }}
                @endif
            </p>
        </div>

        <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;">
            Acesse o portal para consultar o detalhamento, gerar o Pix ou combinar o pagamento com a sua unidade.
        </p>

        <p style="margin:0 0 24px;">
            <a href="{{ $portalUrl }}"
               style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:8px;">
                Acessar o portal
            </a>
        </p>

        <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
            Se você já realizou o pagamento, desconsidere este e-mail.
        </p>
    </div>
</body>
</html>
