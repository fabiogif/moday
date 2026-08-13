<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->title }}</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            color: #059669;
            margin-bottom: 20px;
        }
        .event-title {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 20px;
        }
        .event-description {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .event-details {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .event-detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .event-detail-item:last-child {
            margin-bottom: 0;
        }
        .event-detail-item strong {
            color: #059669;
            min-width: 100px;
            display: inline-block;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
        }
        .promotion-badge {
            display: inline-block;
            background-color: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
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
        .social-links {
            margin-top: 15px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #10b981;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .header h1 {
                font-size: 22px;
            }
            .event-title {
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
            <h1>Promoção Especial!</h1>
        </div>
        
        <div class="content">
            <div class="promotion-badge">🔥 OFERTA LIMITADA</div>
            
            <p class="greeting">
                Olá, <strong>{{ $client->name }}</strong>!
            </p>
            
            <p class="event-title">{{ $event->title }}</p>
            
            <p class="event-description">
                {!! nl2br(e($event->description)) !!}
            </p>
            
            <div class="event-details">
                <div class="event-detail-item">
                    <strong>📅 Data:</strong>
                    <span>{{ $event->start_date->format('d/m/Y') }}</span>
                </div>
                <div class="event-detail-item">
                    <strong>⏰ Horário:</strong>
                    <span>{{ $event->start_date->format('H:i') }}</span>
                </div>
                <div class="event-detail-item">
                    <strong>⏱️ Duração:</strong>
                    <span>{{ $event->duration_minutes }} minutos</span>
                </div>
                @if($event->location)
                <div class="event-detail-item">
                    <strong>📍 Local:</strong>
                    <span>{{ $event->location }}</span>
                </div>
                @endif
            </div>
            
            <div style="text-align: center;">
                <p style="font-size: 18px; color: #10b981; font-weight: bold; margin: 30px 0 10px 0;">
                    Não perca esta oportunidade! 🎯
                </p>
                <p style="font-size: 14px; color: #6b7280; margin-bottom: 20px;">
                    Aproveite esta promoção exclusiva
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>{{ $tenantName }}</strong></p>
            <p>Você recebeu este e-mail porque é nosso cliente especial.</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
                Este é um e-mail automático, por favor não responda.
            </p>
        </div>
    </div>
</body>
</html>

