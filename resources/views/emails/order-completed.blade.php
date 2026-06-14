<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedido confirmado — #{{ $order->identify }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #2563eb; color: #fff; padding: 24px; text-align: center; }
        .content { padding: 24px; }
        .badge { display: inline-block; background: #dbeafe; color: #1d4ed8; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .total { font-size: 20px; font-weight: bold; color: #2563eb; margin-top: 20px; text-align: right; }
        .footer { padding: 20px 24px; background: #f9fafb; font-size: 12px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 style="margin:0;font-size:22px;">Pedido realizado com sucesso!</h1>
        <p style="margin:8px 0 0;">{{ $tenantName }}</p>
    </div>

    <div class="content">
        <p>Olá, <strong>{{ $clientName }}</strong>!</p>
        <p>Recebemos seu pedido <strong>#{{ $order->identify }}</strong> e ele já está em processamento.</p>
        <p><span class="badge">Status: {{ $status }}</span></p>

        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>Qtd</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="total">TOTAL: R$ {{ number_format((float) $order->total, 2, ',', '.') }}</div>

        <p style="margin-top:24px;">Você receberá atualizações conforme o restaurante preparar seu pedido.</p>
    </div>

    <div class="footer">
        <p>Guarde o número <strong>#{{ $order->identify }}</strong> para acompanhamento.</p>
        @if($trackUrl)
            <p><a href="{{ $trackUrl }}">Acompanhar pedido online</a></p>
        @endif
        <p>Dúvidas? Responda este e-mail ou contate {{ config('mail.support_to') }}</p>
    </div>
</div>
</body>
</html>
