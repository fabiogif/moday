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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
            color: #d97706;
            margin-bottom: 20px;
        }
        .event-title {
            font-size: 24px;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 20px;
        }
        .event-description {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .event-details {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
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
            color: #d97706;
            min-width: 100px;
            display: inline-block;
        }
        .alert-box {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .alert-box p {
            margin: 0;
            color: #92400e;
            font-weight: 600;
        }
        .importance-badge {
            display: inline-block;
            background-color: #dc2626;
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
            <div class="icon">📢</div>
            <h1>Aviso Importante</h1>
        </div>
        
        <div class="content">
            <div class="importance-badge">⚠️ IMPORTANTE</div>
            
            <p class="greeting">
                Olá, <strong>{{ $client->name }}</strong>!
            </p>
            
            <p class="event-title">{{ $event->title }}</p>
            
            <div class="alert-box">
                <p>📋 Leia atentamente as informações abaixo</p>
            </div>
            
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
            
            <div style="text-align: center; margin-top: 30px;">
                <p style="font-size: 16px; color: #92400e; font-weight: 600; margin: 0;">
                    Mantenha-se informado! 📱
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>{{ $tenantName }}</strong></p>
            <p>Este é um aviso importante para nossos clientes.</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
                Este é um e-mail automático, por favor não responda.
            </p>
        </div>
    </div>
</body>
</html>

