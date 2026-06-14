<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ContactSendRequest;
use App\Services\ContactService;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contactService) {}
    /**
     * Enviar mensagem de contato
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(ContactSendRequest $request)
    {
        try {
            $this->contactService->send($request->validated());
            return ApiResponseClass::sendResponse('', 'Mensagem enviada com sucesso');
        } catch (\Exception $e) {
            return ApiResponseClass::throw($e, 'Erro ao enviar mensagem');
        }
    }
}
