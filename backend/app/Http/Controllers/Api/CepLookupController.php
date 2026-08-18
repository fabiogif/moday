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

        $result = $this->cepLookupService->lookup($clean);

        if (!$result) {
            return ApiResponseClass::sendResponse(null, 'CEP não encontrado', 404);
        }

        $city = $result['city'];

        return ApiResponseClass::sendResponse([
            'address' => $result['address'],
            'neighborhood' => $result['neighborhood'],
            'complement' => $result['complement'],
            'zip_code' => $result['zip_code'],
            'city' => $city ? (new CityResource($city))->resolve() : null,
            'state' => $city?->state ? (new StateResource($city->state))->resolve() : null,
        ], 'CEP encontrado', 200);
    }
}
