<?php

namespace App\Http\Controllers\Api\Auth;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreClient;
use App\Http\Resources\ClientResource;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;

class RegisterApiController extends Controller
{
    public function __construct(protected ClientService $clientService)
    {
    }
    public function store(StoreClient $request):JsonResponse
    {
        try {
            $client = $this->clientService->createClient($request->all());
            return ApiResponseClass::sendResponse(new ClientResource($client), 'Cliente cadastrado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

}
