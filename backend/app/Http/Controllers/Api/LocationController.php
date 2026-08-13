<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetCitiesRequest;
use App\Http\Requests\ResolveCepLocationRequest;
use App\Http\Requests\SearchCitiesRequest;
use App\Http\Resources\CityResource;
use App\Http\Resources\StateResource;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
                StateResource::collection($states)->resolve(),
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

            if (!$data) {
                return ApiResponseClass::sendResponse('', 'Estado não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                [
                    'state' => (new StateResource($data['state']))->resolve(),
                    'cities' => CityResource::collection($data['cities'])->resolve(),
                ],
                'Cidades carregadas com sucesso'
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar cidades do estado');
        }
    }

    /**
     * GET /api/cities
     */
    public function getAllCities(GetCitiesRequest $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 100);
            $search = $request->input('search');

            $cities = $this->locationService->getAllCities($perPage, $search);

            return ApiResponseClass::sendResponsePaginate(
                CityResource::class,
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
                CityResource::collection($capitals)->resolve(),
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
            $cities = $this->locationService->searchCities($request->input('q'));

            return ApiResponseClass::sendResponse(
                CityResource::collection($cities)->resolve(),
                'Busca realizada com sucesso'
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar cidades');
        }
    }

    /**
     * GET /api/location/resolve-cep?ibge=&uf=&city=
     */
    public function resolveFromCep(ResolveCepLocationRequest $request): JsonResponse
    {
        try {
            $city = $this->locationService->resolveCityFromCep(
                $request->input('ibge'),
                $request->input('uf'),
                $request->input('city')
            );

            if (!$city) {
                return ApiResponseClass::sendResponse('', 'Cidade não encontrada na base local', 404);
            }

            return ApiResponseClass::sendResponse(
                (new CityResource($city))->resolve(),
                'Cidade resolvida com sucesso'
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao resolver cidade');
        }
    }
}
