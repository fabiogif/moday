<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Http\Requests\VerifyBankAccountRequest;
use App\Http\Resources\BankAccountLogResource;
use App\Http\Resources\BankAccountResource;
use App\Services\BankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    public function __construct(
        private readonly BankAccountService $bankAccountService
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
        
        if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
        }
        
            $accounts = $this->bankAccountService->listAccounts($user->tenant_id);

            return ApiResponseClass::sendResponse(
                BankAccountResource::collection($accounts),
                '',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao listar contas bancárias.');
        }
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
            }
            
            $account = $this->bankAccountService->createAccount($user->tenant_id, $request->validated());

            return ApiResponseClass::sendResponse(
                new BankAccountResource($account),
                'Conta bancária cadastrada com sucesso.',
                201
            );
        } catch (\RuntimeException $runtimeException) {
            return ApiResponseClass::validationError(['account' => [$runtimeException->getMessage()]]);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao cadastrar conta bancária.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
        
        if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
        }
        
            $account = $this->bankAccountService->getAccount($uuid, $user->tenant_id);

            return ApiResponseClass::sendResponse(
                new BankAccountResource($account),
                '',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFoundException) {
            return ApiResponseClass::sendResponse('', 'Conta bancaria nao encontrada', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao carregar conta bancária.');
        }
    }

    public function update(UpdateBankAccountRequest $request, string $uuid): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
        }

            $account = $this->bankAccountService->updateAccount($uuid, $user->tenant_id, $request->validated());

            return ApiResponseClass::sendResponse(
                new BankAccountResource($account),
                'Conta bancária atualizada com sucesso.',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFoundException) {
            return ApiResponseClass::sendResponse('', 'Conta bancária não encontrada.', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao atualizar conta bancária.');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
            }

            $this->bankAccountService->deleteAccount($uuid, $user->tenant_id);

            return ApiResponseClass::sendResponse('', 'Conta bancária excluída com sucesso.', 200);
        } catch (\RuntimeException $runtimeException) {
            return response()->json([
                'success' => false,
                'message' => $runtimeException->getMessage(),
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFoundException) {
            return ApiResponseClass::sendResponse('', 'Conta bancária não encontrada.', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao excluir conta bancária.');
        }
    }

    public function setPrimary(string $uuid): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
            }

            $account = $this->bankAccountService->setPrimary($uuid, $user->tenant_id);

            return ApiResponseClass::sendResponse(
                new BankAccountResource($account),
                'Conta definida como principal.',
                200
            );
        } catch (\RuntimeException $runtimeException) {
            return response()->json([
                'success' => false,
                'message' => $runtimeException->getMessage(),
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFoundException) {
            return ApiResponseClass::sendResponse('', 'Conta bancária não encontrada.', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao definir conta principal.');
        }
    }

    public function verify(VerifyBankAccountRequest $request, string $uuid): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
            }

            $account = $this->bankAccountService->verify(
                $uuid,
                $user->tenant_id,
                $request->validated()['verification_method']
            );

            return ApiResponseClass::sendResponse(
                new BankAccountResource($account),
                'Conta verificada com sucesso.',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFoundException) {
            return ApiResponseClass::sendResponse('', 'Conta bancária não encontrada.', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao verificar conta bancária.');
        }
    }

    public function banks(): JsonResponse
    {
        try {
            $banks = $this->bankAccountService->listBanks();

            return ApiResponseClass::sendResponse($banks, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao listar bancos.');
    }
    }

    public function logs(string $uuid): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não associado a um tenant.');
            }

            $logs = $this->bankAccountService->listLogs($uuid, $user->tenant_id);

            return ApiResponseClass::sendResponse(
                BankAccountLogResource::collection($logs),
                '',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFoundException) {
            return ApiResponseClass::sendResponse('', 'Conta bancaria nao encontrada', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::throw($ex, 'Erro ao listar logs da conta bancária.');
        }
    }
}

