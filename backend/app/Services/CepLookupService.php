<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CepLookupService
{
    public function __construct(
        private readonly LocationService $locationService
    ) {}

    /**
     * @return array{address: string, neighborhood: string, complement: string, zip_code: string, city: ?\App\Models\City, uf: ?string, localidade: ?string, estado: ?string}|null
     */
    public function lookup(string $cep): ?array
    {
        $baseUrl = rtrim((string) config('services.viacep.base_url', 'https://viacep.com.br'), '/');
        $timeout = (int) config('services.viacep.timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get("{$baseUrl}/ws/{$cep}/json/");
        } catch (\Throwable $ex) {
            Log::error('ViaCEP lookup exception', ['cep' => $cep, 'error' => $ex->getMessage()]);

            throw $ex;
        }

        if (!$response->successful()) {
            Log::warning('ViaCEP lookup failed', ['cep' => $cep, 'status' => $response->status()]);

            throw new \RuntimeException('Falha ao consultar o serviço de CEP');
        }

        $data = $response->json() ?? [];

        $erro = $data['erro'] ?? false;
        if ($erro === true || $erro === 'true' || empty($data['cep'])) {
            return null;
        }

        $city = $this->locationService->resolveCityFromCep(
            $data['ibge'] ?? null,
            $data['uf'] ?? null,
            $data['localidade'] ?? null
        );

        return [
            'address' => $data['logradouro'] ?? '',
            'neighborhood' => $data['bairro'] ?? '',
            'complement' => $data['complemento'] ?? '',
            'zip_code' => $data['cep'] ?? $cep,
            'city' => $city,
            'uf' => $data['uf'] ?? null,
            'localidade' => $data['localidade'] ?? null,
            'estado' => $data['estado'] ?? ($data['uf'] ?? null),
        ];
    }
}
