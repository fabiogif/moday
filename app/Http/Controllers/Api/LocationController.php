<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetCitiesRequest;
use App\Http\Requests\SearchCitiesRequest;
use App\Http\Response\CityResponse;
use App\Http\Response\StateResponse;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService
    ) {}

    /**
     * GET /api/states
     */
    public function getStates(): JsonResponse
    {
        try {
            $states = $this->locationService->getAllStates();

            return ApiResponseClass::sendResponse(
                StateResponse::collection($states),
                'Estados carregados com sucesso'
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar estados');
        }
    }

    /**
     * GET /api/states/{state}/cities — aceita id numérico ou UF
     */
    public function getCitiesByState(string $state): JsonResponse
    {
        try {
            $data = $this->locationService->getCitiesByStateKey($state);

            return ApiResponseClass::sendResponse(
                CityResponse::stateWithCities($data),
                'Cidades carregadas com sucesso'
            );
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                return ApiResponseClass::sendResponse('', 'Estado não encontrado', 404);
            }

            return ApiResponseClass::rollback($e, 'Erro ao buscar cidades do estado');
        }
    }

    /**
     * GET /api/cities
     */
    public function getAllCities(GetCitiesRequest $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 100);
            $search = $request->input('search');

            $cities = $this->locationService->getAllCities($perPage, $search);

            return ApiResponseClass::sendResponsePaginate(
                CityResponse::class,
                $cities
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar cidades');
        }
    }

    /**
     * GET /api/cities/capitals
     */
    public function getCapitals(): JsonResponse
    {
        try {
            $capitals = $this->locationService->getCapitalCities();

            return ApiResponseClass::sendResponse(
                CityResponse::collection($capitals),
                'Capitais carregadas com sucesso'
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar capitais');
        }
    }

    /**
     * GET /api/cities/search?q=termo
     */
    public function searchCities(SearchCitiesRequest $request): JsonResponse
    {
        try {
            $search = $request->input('q');
            $cities = $this->locationService->searchCities($search);

            return ApiResponseClass::sendResponse(
                CityResponse::collection($cities),
                'Busca realizada com sucesso'
            );
        } catch (\Exception $e) {
            if ($e->getCode() === 400) {
                return ApiResponseClass::validationError([], $e->getMessage());
            }

            return ApiResponseClass::rollback($e, 'Erro ao buscar cidades');
        }
    }

    /**
     * GET /api/location/resolve-cep?ibge=&uf=&city=
     * Resolve município local a partir dos dados retornados pelo ViaCEP.
     */
    public function resolveFromCep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ibge' => ['nullable', 'string', 'max:10'],
            'uf' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $city = $this->locationService->resolveCityFromCep(
                $validated['ibge'] ?? null,
                isset($validated['uf']) ? strtoupper($validated['uf']) : null,
                $validated['city'] ?? null
            );

            if (!$city) {
                return ApiResponseClass::sendResponse('', 'Cidade não encontrada na base local', 404);
            }

            $city->loadMissing('state');

            return ApiResponseClass::sendResponse(
                [
                    'success' => true,
                    'data' => CityResponse::single($city),
                ],
                'Cidade resolvida com sucesso'
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao resolver cidade');
        }
    }
}
