<?php

namespace App\Jobs;

use App\Models\Visit;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVisitWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly Visit $visit,
        private readonly string $eventType,
        private readonly ?Visit $originalVisit = null,
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(EvolutionApiService $evolutionApi): void
    {
        $this->visit->loadMissing(['client', 'user', 'tenant']);

        $instance = $this->visit->tenant?->evolution_instance;
        $phone = $this->visit->client?->whatsapp ?? $this->visit->client?->phone;

        if (!$instance || !$phone) {
            Log::info('SendVisitWhatsAppNotification: skip — instância ou telefone não configurado', [
                'visit_id' => $this->visit->id,
                'has_instance' => (bool) $instance,
                'has_phone' => (bool) $phone,
            ]);

            return;
        }

        $sent = $evolutionApi->sendText($instance, $phone, $this->buildMessage());

        if (!$sent) {
            throw new \RuntimeException("Evolution API retornou erro para a visita #{$this->visit->id}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendVisitWhatsAppNotification: job falhou definitivamente após todas as tentativas', [
            'visit_id' => $this->visit->id,
            'event_type' => $this->eventType,
            'error' => $exception->getMessage(),
        ]);
    }

    private function buildMessage(): string
    {
        $visit = $this->visit;
        $clientName = $visit->client?->trade_name ?? $visit->client?->name ?? 'Cliente';
        $date = \Carbon\Carbon::parse($visit->scheduled_date)->format('d/m/Y');

        if ($this->eventType === 'cancelled') {
            return implode("\n", [
                "❌ *Visita Cancelada*",
                '',
                "Olá, {$clientName}! A visita marcada para {$date} às {$visit->scheduled_start_time} foi cancelada.",
            ]);
        }

        if ($this->eventType === 'rescheduled') {
            $oldDate = $this->originalVisit ? \Carbon\Carbon::parse($this->originalVisit->scheduled_date)->format('d/m/Y') : null;

            return implode("\n", array_filter([
                "🔄 *Visita Reagendada*",
                '',
                "Olá, {$clientName}!",
                $oldDate ? "Sua visita de {$oldDate} foi remarcada." : null,
                "📅 *Nova data:* {$date}",
                "⏰ *Horário:* {$visit->scheduled_start_time} às {$visit->scheduled_end_time}",
            ]));
        }

        return implode("\n", [
            "✅ *Visita Agendada*",
            '',
            "Olá, {$clientName}!",
            "📅 *Data:* {$date}",
            "⏰ *Horário:* {$visit->scheduled_start_time} às {$visit->scheduled_end_time}",
        ]);
    }
}
