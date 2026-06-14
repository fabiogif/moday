<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao Alba Tec</title>
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
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .icon {
            font-size: 60px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #4F46E5;
            margin-bottom: 20px;
        }
        .welcome-title {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 20px;
        }
        .welcome-text {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .info-box {
            background-color: #f0f4ff;
            border-left: 4px solid #4F46E5;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .info-item:last-child {
            margin-bottom: 0;
        }
        .info-item strong {
            color: #4F46E5;
            min-width: 120px;
            display: inline-block;
        }
        .plan-badge {
            display: inline-block;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.4);
        }
        .features {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .features h3 {
            color: #4F46E5;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
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
                font-size: 22px;
            }
            .welcome-title {
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
            <div class="icon">🎉</div>
            <h1>Bem-vindo ao Alba Tec!</h1>
        </div>
        
        <div class="content">
            <p class="greeting">
                Olá, <strong>{{ $user->name }}</strong>!
            </p>
            
            <p class="welcome-title">Sua empresa foi cadastrada com sucesso! 🚀</p>
            
            <p class="welcome-text">
                Estamos muito felizes em ter <strong>{{ $tenant->name }}</strong> como parte da nossa plataforma.
                Agora você pode gerenciar seu negócio de forma completa e eficiente.
            </p>
            
            <div class="info-box">
                <div class="info-item">
                    <strong>🏢 Empresa:</strong>
                    <span>{{ $tenant->name }}</span>
                </div>
                <div class="info-item">
                    <strong>📧 E-mail:</strong>
                    <span>{{ $user->email }}</span>
                </div>
                <div class="info-item">
                    <strong>👤 Usuário:</strong>
                    <span>{{ $user->name }}</span>
                </div>
                @if($tenant->slug)
                <div class="info-item">
                    <strong>🔗 URL:</strong>
                    <span>{{ $tenant->slug }}.albatech.com.br</span>
                </div>
                @endif
            </div>

            <div style="text-align: center;">
                <div class="plan-badge">
                    Plano: {{ $plan->name }}
                </div>
            </div>

            @if($plan->details && $plan->details->count() > 0)
            <div class="features">
                <h3>Benefícios do seu plano:</h3>
                <ul>
                    @foreach($plan->details as $detail)
                    <li>{{ $detail->description }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            <div style="text-align: center; margin-top: 30px;">
                <p style="font-size: 16px; color: #4F46E5; font-weight: bold; margin-bottom: 10px;">
                    Pronto para começar? 🎯
                </p>
                <p style="font-size: 14px; color: #6b7280; margin-bottom: 20px;">
                    Acesse sua conta e comece a gerenciar seu negócio agora mesmo
                </p>
                <a href="{{ $loginUrl }}" class="cta-button">
                    Acessar Minha Conta
                </a>
            </div>

            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 14px; color: #6b7280; line-height: 1.8;">
                    <strong>Dúvidas ou precisa de ajuda?</strong><br>
                    Nossa equipe de suporte está pronta para ajudar você em
                    <a href="mailto:{{ config('mail.support_to') }}" style="color: #2563eb; text-decoration: none;">{{ config('mail.support_to') }}</a>
                    ou responda este e-mail.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Alba Tec</strong></p>
            <p>Sistema de Gestão para Restaurantes e Lanchonetes</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
                Este é um e-mail automático de boas-vindas. Se você não se cadastrou, por favor ignore este e-mail.
            </p>
        </div>
    </div>
</body>
</html>






