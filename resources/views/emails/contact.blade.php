<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato — DistribTec</title>
</head>
<body style="margin:0;padding:0;background:#F4F7FA;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0B0B0B;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F4F7FA;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#FFFFFF;border:1px solid #D6DDE4;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="background:#006A91;padding:24px 28px;text-align:center;">
                        <h1 style="margin:0;color:#FFFFFF;font-size:20px;">DistribTec — Contato do site</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px;color:#424F56;font-size:15px;"><strong style="color:#006A91;">Assunto:</strong> {{ $subject }}</p>
                        <p style="margin:0 0 12px;color:#424F56;font-size:15px;"><strong style="color:#006A91;">Nome:</strong> {{ $name }}</p>
                        <p style="margin:0 0 12px;color:#424F56;font-size:15px;"><strong style="color:#006A91;">E-mail:</strong> {{ $email }}</p>
                        <div style="margin-top:20px;padding:16px;background:#EDF2F4;border-left:4px solid #006A91;border-radius:8px;color:#0B0B0B;font-size:15px;white-space:pre-wrap;">{{ $body }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="background:#F4F7FA;border-top:1px solid #D6DDE4;padding:18px 28px;text-align:center;font-size:12px;color:#7A8790;">
                        Enviado automaticamente pelo formulário de contato do DistribTec.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
