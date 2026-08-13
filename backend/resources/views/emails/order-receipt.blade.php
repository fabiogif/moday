<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo — Pedido #{{ $order->identify }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #059669; color: #fff; padding: 24px; text-align: center; }
        .content { padding: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .total { font-size: 20px; font-weight: bold; color: #059669; margin-top: 20px; text-align: right; }
        .footer { padding: 20px 24px; background: #f9fafb; font-size: 12px; color: #6b7280; text-align: center; }
        .meta { margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 style="margin:0;font-size:22px;">{{ $tenantName }}</h1>
        <p style="margin:8px 0 0;">Recibo do pedido #{{ $order->identify }}</p>
    </div>

    <div class="content">
        <p class="meta"><strong>Cliente:</strong> {{ $clientName }}</p>
        <p class="meta"><strong>Data:</strong> {{ $order->created_at?->format('d/m/Y H:i') }}</p>
        <p class="meta"><strong>Pagamento:</strong> {{ $paymentMethod }}</p>
        @if($order->is_delivery)
            <p class="meta"><strong>Entrega:</strong> {{ $order->delivery_address }}, {{ $order->delivery_number }} — {{ $order->delivery_neighborhood }}, {{ $order->delivery_city }}/{{ $order->delivery_state }}</p>
        @else
            <p class="meta"><strong>Modalidade:</strong> Retirada no local</p>
        @endif

        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>Qtd</th>
                <th>Preço</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>R$ {{ number_format($item['unit_price'], 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="total">TOTAL: R$ {{ number_format((float) $order->total, 2, ',', '.') }}</div>

        @if($order->comment || $order->delivery_notes)
            <p style="margin-top:20px;"><strong>Observações:</strong><br>{{ $order->comment ?? $order->delivery_notes }}</p>
        @endif
    </div>

    <div class="footer">
        <p>Obrigado pela preferência!</p>
        @if($trackUrl)
            <p><a href="{{ $trackUrl }}">Acompanhar pedido</a></p>
        @endif
    </div>
</div>
</body>
</html>
