<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ App\Mail\VisitNotificationMail::SUBJECTS[$eventType] ?? 'Atualização da visita' }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 620px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .header { padding: 28px 24px; color: #fff; }
        .header.scheduled { background: #2563eb; }
        .header.cancelled { background: #dc2626; }
        .header.rescheduled { background: #d97706; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .header p  { margin: 6px 0 0; font-size: 14px; opacity: .85; }
        .content { padding: 24px; }
        .meta { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .meta span:first-child { color: #6b7280; }
        .footer { padding: 16px 24px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header {{ $eventType }}">
        <h1>{{ $tenantName }}</h1>
        <p>{{ App\Mail\VisitNotificationMail::SUBJECTS[$eventType] ?? 'Atualização da visita' }}</p>
    </div>

    <div class="content">
        <p style="margin:0 0 16px;font-size:15px;">Olá, <strong>{{ $clientName }}</strong>!</p>

        @if($eventType === 'scheduled')
            <p style="margin:0 0 20px;font-size:14px;color:#4b5563;">Uma visita foi agendada com você. Confira os detalhes:</p>
        @elseif($eventType === 'cancelled')
            <p style="margin:0 0 20px;font-size:14px;color:#4b5563;">A visita abaixo foi <strong>cancelada</strong>.</p>
        @else
            <p style="margin:0 0 20px;font-size:14px;color:#4b5563;">Sua visita foi <strong>reagendada</strong>. Confira a nova data:</p>
        @endif

        @if($eventType === 'rescheduled' && $originalVisit)
        <div class="meta"><span>Data anterior</span><span style="text-decoration:line-through;color:#9ca3af;">{{ \Carbon\Carbon::parse($originalVisit->scheduled_date)->format('d/m/Y') }} às {{ $originalVisit->scheduled_start_time }}</span></div>
        @endif

        <div class="meta"><span>Data</span><strong>{{ \Carbon\Carbon::parse($visit->scheduled_date)->format('d/m/Y') }}</strong></div>
        <div class="meta"><span>Horário</span><span>{{ $visit->scheduled_start_time }} às {{ $visit->scheduled_end_time }}</span></div>
        @if($visit->user)
        <div class="meta"><span>Vendedor(a)</span><span>{{ $visit->user->name }}</span></div>
        @endif
        @if($visit->objective_notes)
        <div class="meta"><span>Objetivo</span><span>{{ $visit->objective_notes }}</span></div>
        @endif
    </div>

    <div class="footer">
        <p>Em caso de dúvidas, entre em contato com <strong>{{ $tenantName }}</strong>.</p>
        <p style="margin:4px 0 0;">Este e-mail foi gerado automaticamente pelo sistema DistribTec.</p>
    </div>
</div>
</body>
</html>
