<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ContactSendRequest;
use App\Services\ContactService;
use Illuminate\Support\Facades\Log;

class ContactApiController extends Controller
{
    public function __construct(private readonly ContactService $contactService) {}
    /**
     * Enviar mensagem de contato por email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(ContactSendRequest $request)
    {
        try {
            $data = $request->validated();
            $this->contactService->send($data);

            Log::info('Email de contato enviado', [
                'name' => $data['firstName'] . ' ' . $data['lastName'],
                'email' => $data['email'],
                'subject' => $data['subject']
            ]);

            return ApiResponseClass::sendResponse('', 'Mensagem enviada com sucesso! Entraremos em contato em breve.', 200);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de contato: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->validated()
            ]);

            return ApiResponseClass::throw($e, 'Erro ao enviar mensagem. Por favor, tente novamente mais tarde.');
        }
    }
}
