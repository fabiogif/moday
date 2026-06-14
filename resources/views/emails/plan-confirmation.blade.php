<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de Plano - Alba Tec</title>
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
        .header.migration {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
        .migration .greeting {
            color: #2563eb;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 20px;
        }
        .migration .title {
            color: #2563eb;
        }
        .text {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .plan-box {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .migration .plan-box {
            background-color: #eff6ff;
            border-left-color: #3b82f6;
        }
        .plan-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .plan-item:last-child {
            margin-bottom: 0;
        }
        .plan-item strong {
            color: #059669;
            min-width: 140px;
            display: inline-block;
        }
        .migration .plan-item strong {
            color: #2563eb;
        }
        .plan-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        .migration .plan-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .comparison {
            display: flex;
            gap: 15px;
            margin: 30px 0;
        }
        .comparison-item {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .comparison-item.old {
            background-color: #fef2f2;
            border: 2px solid #fecaca;
        }
        .comparison-item.new {
            background-color: #f0fdf4;
            border: 2px solid #86efac;
        }
        .comparison-item h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #6b7280;
        }
        .comparison-item.old h4 {
            color: #991b1b;
        }
        .comparison-item.new h4 {
            color: #059669;
        }
        .comparison-item .plan-name {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        .comparison-item.old .plan-name {
            color: #dc2626;
        }
        .comparison-item.new .plan-name {
            color: #10b981;
        }
        .arrow {
            display: flex;
            align-items: center;
            font-size: 24px;
            color: #3b82f6;
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
        .migration .cta-button {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
        }
        .migration .cta-button:hover {
            box-shadow: 0 6px 8px rgba(59, 130, 246, 0.4);
        }
        .features {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .features h3 {
            color: #059669;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .migration .features h3 {
            color: #2563eb;
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
            .title {
                font-size: 20px;
            }
            .content {
                padding: 30px 20px;
            }
            .comparison {
                flex-direction: column;
            }
            .arrow {
                transform: rotate(90deg);
                justify-content: center;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $isMigration ? 'migration' : '' }}">
            <div class="icon">{{ $isMigration ? '🚀' : '✅' }}</div>
            <h1>{{ $isMigration ? 'Plano Atualizado!' : 'Plano Confirmado!' }}</h1>
        </div>
        
        <div class="content {{ $isMigration ? 'migration' : '' }}">
            <p class="greeting">
                Olá, <strong>{{ $tenant->name }}</strong>!
            </p>
            
            <p class="title">
                {{ $isMigration ? 'Sua migração de plano foi realizada com sucesso!' : 'Seu plano foi confirmado com sucesso!' }}
            </p>
            
            <p class="text">
                @if($isMigration)
                    Sua empresa migrou do plano <strong>{{ $oldPlan->name }}</strong> para o plano <strong>{{ $plan->name }}</strong>.
                    Todas as funcionalidades do novo plano já estão disponíveis para você.
                @else
                    Sua empresa está agora no plano <strong>{{ $plan->name }}</strong>.
                    Todas as funcionalidades do seu plano já estão disponíveis.
                @endif
            </p>

            @if($isMigration)
            <div class="comparison">
                <div class="comparison-item old">
                    <h4>Plano Anterior</h4>
                    <div class="plan-name">{{ $oldPlan->name }}</div>
                    @if($oldPlan->price > 0)
                    <div style="font-size: 14px; color: #6b7280;">
                        R$ {{ number_format($oldPlan->price, 2, ',', '.') }}/mês
                    </div>
                    @endif
                </div>
                <div class="arrow">→</div>
                <div class="comparison-item new">
                    <h4>Novo Plano</h4>
                    <div class="plan-name">{{ $plan->name }}</div>
                    @if($plan->price > 0)
                    <div style="font-size: 14px; color: #6b7280;">
                        R$ {{ number_format($plan->price, 2, ',', '.') }}/mês
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="plan-box">
                <div class="plan-item">
                    <strong>🏢 Empresa:</strong>
                    <span>{{ $tenant->name }}</span>
                </div>
                <div class="plan-item">
                    <strong>📦 Plano Atual:</strong>
                    <span>{{ $plan->name }}</span>
                </div>
                @if($plan->price > 0)
                <div class="plan-item">
                    <strong>💰 Valor:</strong>
                    <span>R$ {{ number_format($plan->price, 2, ',', '.') }}/mês</span>
                </div>
                @endif
                @if($migration && $migration->migrated_at)
                <div class="plan-item">
                    <strong>📅 Data da Migração:</strong>
                    <span>{{ $migration->migrated_at->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>

            <div style="text-align: center;">
                <div class="plan-badge">
                    {{ $plan->name }}
                </div>
            </div>

            @if($plan->details && $plan->details->count() > 0)
            <div class="features">
                <h3>Benefícios do seu novo plano:</h3>
                <ul>
                    @foreach($plan->details as $detail)
                    <li>{{ $detail->description }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            <div style="text-align: center; margin-top: 30px;">
                <p style="font-size: 16px; color: #059669; font-weight: bold; margin-bottom: 10px;">
                    Acesse seu painel agora! 🎯
                </p>
                <p style="font-size: 14px; color: #6b7280; margin-bottom: 20px;">
                    Explore todas as funcionalidades disponíveis no seu plano
                </p>
                <a href="{{ $dashboardUrl }}" class="cta-button">
                    Acessar Dashboard
                </a>
            </div>

            @if($migration && $migration->notes)
            <div style="margin-top: 30px; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
                <p style="margin: 0; font-size: 14px; color: #92400e;">
                    <strong>Observação:</strong> {{ $migration->notes }}
                </p>
            </div>
            @endif

            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 14px; color: #6b7280; line-height: 1.8;">
                    <strong>Dúvidas sobre seu plano?</strong><br>
                    Nossa equipe de suporte está pronta para ajudar você. Entre em contato através do painel administrativo.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Alba Tec</strong></p>
            <p>Sistema de Gestão para Restaurantes e Lanchonetes</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
                Este é um e-mail automático de confirmação. Se você não realizou esta ação, entre em contato conosco imediatamente.
            </p>
        </div>
    </div>
</body>
</html>






