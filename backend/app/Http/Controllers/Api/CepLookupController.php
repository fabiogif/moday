<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Resources\CityResource;
use App\Http\Resources\StateResource;
use App\Services\CepLookupService;
use Illuminate\Http\JsonResponse;

class CepLookupController extends Controller
{
    public function __construct(
        private readonly CepLookupService $cepLookupService,
    ) {}

    /**
     * GET /api/cep/{cep}
     */
    public function __invoke(string $cep): JsonResponse
    {
        $clean = preg_replace('/\D/', '', $cep) ?? '';

        if (strlen($clean) !== 8) {
            return ApiResponseClass::sendResponse(null, 'CEP inválido', 422);
        }

        try {
            $result = $this->cepLookupService->lookup($clean);
        } catch (\Throwable) {
            return ApiResponseClass::sendResponse(null, 'Erro ao consultar CEP. Tente novamente.', 503);
        }

        if (!$result) {
            return ApiResponseClass::sendResponse(null, 'CEP não encontrado', 404);
        }

        $city = $result['city'];
        $uf = $result['uf'] ?? null;
        $localidade = $result['localidade'] ?? null;
        $estado = $result['estado'] ?? $uf;

        return ApiResponseClass::sendResponse([
            'address' => $result['address'],
            'neighborhood' => $result['neighborhood'],
            'complement' => $result['complement'],
            'zip_code' => $result['zip_code'],
            'city' => $city
                ? (new CityResource($city))->resolve()
                : ($localidade ? [
                    'id' => null,
                    'name' => $localidade,
                    'ibge_code' => null,
                    'is_capital' => false,
                ] : null),
            'state' => $city?->state
                ? (new StateResource($city->state))->resolve()
                : ($uf ? [
                    'id' => null,
                    'uf' => $uf,
                    'name' => $estado,
                    'region' => null,
                ] : null),
        ], 'CEP encontrado', 200);
    }
}
