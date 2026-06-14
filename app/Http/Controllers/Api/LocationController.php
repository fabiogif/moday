<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetCitiesRequest;
use App\Http\Requests\SearchCitiesRequest;
use App\Http\Response\StateResponse;
use App\Http\Response\CityResponse;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService
    ) {}

    /**
     * Get all states
     * GET /api/states
     * 
     * @return JsonResponse
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
     * Get cities by state UF
     * GET /api/states/{uf}/cities
     * 
     * @param string $uf
     * @return JsonResponse
     */
    public function getCitiesByState(string $uf): JsonResponse
    {
        try {
            $data = $this->locationService->getCitiesByStateUf($uf);
            
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
     * Get all cities (with pagination)
     * GET /api/cities
     * 
     * @param GetCitiesRequest $request
     * @return JsonResponse
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
     * Get capital cities
     * GET /api/cities/capitals
     * 
     * @return JsonResponse
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
     * Search cities by name
     * GET /api/cities/search?q=termo
     * 
     * @param SearchCitiesRequest $request
     * @return JsonResponse
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
}

