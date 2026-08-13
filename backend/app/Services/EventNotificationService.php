<?php

namespace App\Services;

use App\Mail\EventNotificationMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EventNotificationService
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService
    ) {}

    /**
     * @param \App\Models\Event $event
     * @param Collection<int, \App\Models\Client> $clients
     */
    public function sendNotifications(\App\Models\Event $event, Collection $clients, array $channels): array
    {
        $results = [
            'email' => ['sent' => 0, 'failed' => 0],
            'whatsapp' => ['sent' => 0, 'failed' => 0],
            'sms' => ['sent' => 0, 'failed' => 0],
        ];

        foreach ($clients as $client) {
            if (in_array('email', $channels)) {
                $emailResult = $this->sendEmail($event, $client);
                $results['email'][$emailResult ? 'sent' : 'failed']++;
            }

            if (in_array('whatsapp', $channels)) {
                $whatsappResult = $this->sendWhatsApp($event, $client);
                $results['whatsapp'][$whatsappResult ? 'sent' : 'failed']++;
            }

            if (in_array('sms', $channels)) {
                $smsResult = $this->sendSms($event, $client);
                $results['sms'][$smsResult ? 'sent' : 'failed']++;
            }
        }

        return $results;
    }

    private function sendEmail(\App\Models\Event $event, \App\Models\Client $client): bool
    {
        try {
            if (!$client->email) {
                Log::warning('Cliente sem email', ['client_id' => $client->id]);
                return false;
            }

            $tenantName = $event->tenant->name ?? 'Sistema';

            Mail::to($client->email, $client->name)
                ->send(new EventNotificationMail($event, $client, $tenantName));

            Log::info('Email de evento enviado com template', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'client_id' => $client->id,
                'email' => $client->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de evento: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function sendWhatsApp(\App\Models\Event $event, \App\Models\Client $client): bool
    {
        try {
            if (!$client->phone) {
                Log::warning('Cliente sem telefone', ['client_id' => $client->id]);
                return false;
            }

            $tenantName = $event->tenant->name ?? 'Sistema';
            $message = WhatsAppTemplateService::generateMessage($event, $client, $tenantName);
            $link = WhatsAppTemplateService::generateWhatsAppLink($client->phone, $message);

            Log::info('Link WhatsApp de evento gerado com template', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'client_id' => $client->id,
                'phone' => $client->phone,
                'link' => $link,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao gerar WhatsApp de evento: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function sendSms(\App\Models\Event $event, \App\Models\Client $client): bool
    {
        try {
            if (!$client->phone) {
                return false;
            }

            $message = $this->buildSmsMessage($event);

            Log::info('SMS de evento preparado para envio', [
                'event_id' => $event->id,
                'client_id' => $client->id,
                'phone' => $client->phone,
                'message' => $message,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao preparar SMS de evento: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function buildSmsMessage(\App\Models\Event $event): string
    {
        $startDate = $event->start_date->format('d/m H:i');
        $tenantName = $event->tenant->name ?? 'Sistema';

        $description = strlen($event->description) > 60
            ? substr($event->description, 0, 57) . '...'
            : $event->description;

        return "{$tenantName}: {$event->title} - {$startDate}. {$description}";
    }
}
