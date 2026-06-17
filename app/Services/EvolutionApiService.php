<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.evolution_api.url'), '/');
        $this->apiKey  = (string) config('services.evolution_api.key');
    }

    /**
     * Envia mensagem de texto via Evolution API.
     *
     * @param  string  $instance  Nome da instância configurada na Evolution API
     * @param  string  $number    Número do destinatário (somente dígitos, com DDI)
     * @param  string  $text      Corpo da mensagem
     */
    public function sendText(string $instance, string $number, string $text): bool
    {
        $number = preg_replace('/\D/', '', $number);

        $response = Http::withHeaders([
            'apikey'       => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$this->baseUrl}/message/sendText/{$instance}", [
            'number' => $number,
            'text'   => $text,
        ]);

        if (!$response->successful()) {
            Log::error('EvolutionApiService: falha ao enviar mensagem', [
                'instance' => $instance,
                'number'   => $number,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            return false;
        }

        Log::info('EvolutionApiService: mensagem enviada com sucesso', [
            'instance' => $instance,
            'number'   => $number,
        ]);

        return true;
    }
}
