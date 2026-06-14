<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #4F46E5;
            margin-bottom: 20px;
        }
        .message-content {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            white-space: pre-wrap;
        }
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #6b7280;
        }
        @media only screen and (max-width: 600px) {
            .header h1 {
                font-size: 20px;
            }
            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Alba Tec</h1>
            <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Mensagem da Administração</p>
        </div>
        
        <div class="content">
            <p class="greeting">
                Olá, <strong>{{ $tenant->name }}</strong>!
            </p>
            
            <div class="message-content">
                {!! nl2br(e($message)) !!}
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Alba Tec</strong></p>
            <p>Sistema de Gestão para Restaurantes e Lanchonetes</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
                Este é um e-mail enviado pela administração da plataforma. Para responder, utilize o suporte através do painel administrativo.
            </p>
        </div>
    </div>
</body>
</html>






