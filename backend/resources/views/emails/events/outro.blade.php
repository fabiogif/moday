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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            color: #2563eb;
            margin-bottom: 20px;
        }
        .event-title {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 20px;
        }
        .event-description {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .event-details {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
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
            color: #2563eb;
            min-width: 100px;
            display: inline-block;
        }
        .info-box {
            background-color: #dbeafe;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
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
            <div class="icon">📅</div>
            <h1>Novo Evento</h1>
        </div>
        
        <div class="content">
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
            
            <div class="info-box">
                <p>ℹ️ Anote essa data em sua agenda!</p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>{{ $tenantName }}</strong></p>
            <p>Obrigado por ser nosso cliente!</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
                Este é um e-mail automático, por favor não responda.
            </p>
        </div>
    </div>
</body>
</html>

