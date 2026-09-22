<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\EvolutionApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Verifica se a instância WhatsApp de cada tenant está conectada na Evolution API
 * e avisa por e-mail só quando o estado muda (caiu / voltou).
 */
class EvolutionHealthCheck extends Command
{
    protected $signature = 'evolution:health';

    protected $description = 'Verifica a conexão WhatsApp (Evolution API) dos tenants e alerta por e-mail na mudança de estado';

    public function handle(EvolutionApiService $evolutionApi): int
    {
        $tenants = Tenant::query()
            ->whereNotNull('evolution_instance')
            ->where('evolution_instance', '<>', '')
            ->whereHas('plan', fn ($q) => $q->where('has_whatsapp_notifications', true))
            ->get(['id', 'name', 'email', 'evolution_instance']);

        $allOk = true;

        foreach ($tenants as $tenant) {
            $state = $evolutionApi->connectionState($tenant->evolution_instance);
            $ok    = $state === 'open';
            $allOk = $allOk && $ok;

            $this->line(sprintf('%s [%s]: %s', $tenant->name, $tenant->evolution_instance, $state ?? 'sem resposta'));

            $cacheKey = "evolution_health:{$tenant->id}";
            $wasOk    = Cache::get($cacheKey, true);
            Cache::forever($cacheKey, $ok);

            if ($ok === $wasOk) {
                continue;
            }

            $context = ['tenant_id' => $tenant->id, 'instance' => $tenant->evolution_instance, 'state' => $state];
            $ok
                ? Log::info('EvolutionHealthCheck: WhatsApp reconectado', $context)
                : Log::critical('EvolutionHealthCheck: WhatsApp indisponível', $context);

            $this->notify($tenant, $ok, $state);
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    private function notify(Tenant $tenant, bool $ok, ?string $state): void
    {
        $recipients = array_values(array_filter([$tenant->email, config('services.evolution_api.alert_email')]));
        if (!$recipients) {
            return;
        }

        $subject = $ok
            ? "WhatsApp reconectado — {$tenant->name}"
            : "WhatsApp desconectado — {$tenant->name}";

        $body = $ok
            ? "O WhatsApp de {$tenant->name} voltou a funcionar. Os pedidos do cardápio voltaram a ser enviados automaticamente."
            : "O WhatsApp de {$tenant->name} está indisponível (estado: " . ($state ?? 'Evolution API sem resposta') . ").\n\n"
              . "Enquanto isso, os pedidos do cardápio continuam sendo recebidos no sistema, mas NÃO chegam no WhatsApp. "
              . "Acompanhe os pedidos pelo painel e entre em contato com o suporte para reconectar.";

        try {
            Mail::raw($body, fn ($m) => $m->to($recipients)->subject($subject));
        } catch (\Throwable $e) {
            Log::error('EvolutionHealthCheck: falha ao enviar alerta', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }
}
