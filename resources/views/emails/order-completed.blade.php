<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedido confirmado — #{{ $order->identify }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F7FA;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0B0B0B;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F4F7FA;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#FFFFFF;border:1px solid #D6DDE4;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="background:#006A91;color:#FFFFFF;padding:24px;text-align:center;">
                        <h1 style="margin:0;font-size:20px;">Pedido realizado com sucesso</h1>
                        <p style="margin:8px 0 0;opacity:0.9;">{{ $tenantName }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 12px;color:#424F56;">Olá, <strong style="color:#0B0B0B;">{{ $clientName }}</strong>!</p>
                        <p style="margin:0 0 12px;color:#424F56;">Recebemos seu pedido <strong>#{{ $order->identify }}</strong> e ele já está em processamento.</p>
                        <p style="margin:0 0 16px;">
                            <span style="display:inline-block;background:#EDF2F4;color:#006A91;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:700;">
                                Status: {{ $status }}
                            </span>
                        </p>
                        <table width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;margin-top:8px;">
                            <thead>
                            <tr>
                                <th align="left" style="padding:10px;border-bottom:1px solid #D6DDE4;background:#F4F7FA;font-size:12px;color:#424F56;text-transform:uppercase;">Item</th>
                                <th align="left" style="padding:10px;border-bottom:1px solid #D6DDE4;background:#F4F7FA;font-size:12px;color:#424F56;text-transform:uppercase;">Qtd</th>
                                <th align="left" style="padding:10px;border-bottom:1px solid #D6DDE4;background:#F4F7FA;font-size:12px;color:#424F56;text-transform:uppercase;">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td style="padding:10px;border-bottom:1px solid #D6DDE4;color:#0B0B0B;">{{ $item['name'] }}</td>
                                    <td style="padding:10px;border-bottom:1px solid #D6DDE4;color:#424F56;">{{ $item['quantity'] }}</td>
                                    <td style="padding:10px;border-bottom:1px solid #D6DDE4;color:#424F56;">R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <p style="font-size:20px;font-weight:700;color:#006A91;margin:20px 0 0;text-align:right;">
                            TOTAL: R$ {{ number_format((float) $order->total, 2, ',', '.') }}
                        </p>
                        <p style="margin-top:24px;color:#424F56;">Você receberá atualizações conforme o andamento do pedido.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 24px;background:#F4F7FA;font-size:12px;color:#7A8790;text-align:center;border-top:1px solid #D6DDE4;">
                        <p style="margin:0 0 8px;">Guarde o número <strong>#{{ $order->identify }}</strong> para acompanhamento.</p>
                        @if($trackUrl)
                            <p style="margin:0 0 8px;"><a href="{{ $trackUrl }}" style="color:#006A91;">Acompanhar pedido online</a></p>
                        @endif
                        <p style="margin:0;">Dúvidas? Responda este e-mail ou contate {{ config('mail.support_to') }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
