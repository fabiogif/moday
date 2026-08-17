<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmação — Pedido {{ $order->identify }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 620px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .header { background: #2563eb; color: #fff; padding: 28px 24px; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .header p  { margin: 6px 0 0; font-size: 14px; opacity: .85; }
        .content { padding: 24px; }
        .meta { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .meta span:first-child { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th { background: #f9fafb; padding: 10px; border-bottom: 2px solid #e5e7eb; text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        td { padding: 10px; border-bottom: 1px solid #f3f4f6; }
        .badge-bonus { background: #d1fae5; color: #065f46; font-size: 11px; padding: 2px 6px; border-radius: 9999px; margin-left: 4px; }
        .totals { margin-top: 16px; }
        .totals .row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; }
        .totals .row.total { border-top: 2px solid #e5e7eb; margin-top: 8px; padding-top: 12px; font-weight: 700; font-size: 18px; color: #2563eb; }
        .notes { margin-top: 20px; background: #f9fafb; border-left: 4px solid #2563eb; padding: 12px 16px; font-size: 14px; border-radius: 0 4px 4px 0; }
        .footer { padding: 16px 24px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ $tenantName }}</h1>
        <p>Confirmação do pedido #{{ $order->identify }}</p>
    </div>

    <div class="content">
        <p style="margin:0 0 16px;font-size:15px;">Olá, <strong>{{ $clientName }}</strong>!</p>
        <p style="margin:0 0 20px;font-size:14px;color:#4b5563;">Seu pedido foi <strong>confirmado</strong> com sucesso. Segue abaixo o resumo:</p>

        <div class="meta"><span>Número do pedido</span><strong>{{ $order->identify }}</strong></div>
        <div class="meta"><span>Data</span><span>{{ $order->ordered_at?->format('d/m/Y H:i') ?? $order->created_at?->format('d/m/Y H:i') }}</span></div>
        @if($order->payment_method)
        <div class="meta"><span>Forma de pagamento</span><span>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span></div>
        @endif
        @if($order->payment_term_days)
        <div class="meta"><span>Prazo de pagamento</span><span>{{ $order->payment_term_days }} dias</span></div>
        @endif
        @if($order->estimated_delivery)
        <div class="meta"><span>Previsão de entrega</span><span>{{ \Carbon\Carbon::parse($order->estimated_delivery)->format('d/m/Y') }}</span></div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th style="text-align:center;">Qtd</th>
                    <th style="text-align:right;">Preço Unit.</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>
                        {{ $item['name'] }}
                        @if($item['item_type'] === 'bonificacao')
                            <span class="badge-bonus">Bonificação</span>
                        @endif
                    </td>
                    <td style="text-align:center;">{{ number_format($item['quantity'], 0, ',', '.') }}</td>
                    <td style="text-align:right;">
                        @if($item['item_type'] === 'bonificacao')
                            <span style="color:#6b7280;">Grátis</span>
                        @else
                            R$ {{ number_format($item['unit_price'], 2, ',', '.') }}
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if($item['item_type'] === 'bonificacao')
                            —
                        @else
                            R$ {{ number_format($item['total'], 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>Subtotal</span><span>R$ {{ number_format((float) $order->subtotal, 2, ',', '.') }}</span></div>
            @if((float) $order->freight_amount > 0)
            <div class="row"><span>Frete</span><span>+ R$ {{ number_format((float) $order->freight_amount, 2, ',', '.') }}</span></div>
            @endif
            @if((float) $order->discount_amount > 0)
            <div class="row" style="color:#059669;"><span>Desconto</span><span>- R$ {{ number_format((float) $order->discount_amount, 2, ',', '.') }}</span></div>
            @endif
            <div class="row total"><span>Total</span><span>R$ {{ number_format((float) $order->total, 2, ',', '.') }}</span></div>
        </div>

        @if($order->notes)
        <div class="notes">
            <strong>Observações:</strong><br>{{ $order->notes }}
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Em caso de dúvidas, entre em contato com <strong>{{ $tenantName }}</strong>.</p>
        <p style="margin:4px 0 0;">Este e-mail foi gerado automaticamente pelo sistema DistribTec.</p>
    </div>
</div>
</body>
</html>
