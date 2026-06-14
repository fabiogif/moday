<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AdminEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminEmailController extends Controller
{
    public function __construct(
        private readonly AdminEmailService $emailService
    ) {}

    public function sendBulkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'integer|exists:tenants,id',
            'send_to_tenants' => 'boolean',
            'send_to_newsletter' => 'boolean',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $sendToTenants = $request->boolean('send_to_tenants', !empty($request->input('tenant_ids')));
        $sendToNewsletter = $request->boolean('send_to_newsletter', false);
        $tenantIds = $request->input('tenant_ids', []);

        if (!$sendToTenants && !$sendToNewsletter) {
            throw ValidationException::withMessages([
                'audience' => ['Selecione pelo menos um público: inscritos no informativo ou empresas.'],
            ]);
        }

        if ($sendToTenants && empty($tenantIds)) {
            throw ValidationException::withMessages([
                'tenant_ids' => ['Selecione pelo menos uma empresa.'],
            ]);
        }

        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return ApiResponseClass::unauthorized('Não autorizado.');
        }

        try {
            $result = $this->emailService->sendBulkEmail(
                $admin,
                $tenantIds,
                $request->subject,
                $request->message,
                $sendToTenants,
                $sendToNewsletter
            );

            if (!$result['success']) {
                $messages = [
                    'no_audience' => 'Selecione pelo menos um público para envio.',
                    'no_valid_tenants' => 'Nenhuma empresa válida encontrada.',
                    'no_subscribers' => 'Nenhum inscrito ativo no informativo.',
                    'nothing_queued' => 'Nenhum e-mail pôde ser enfileirado.',
                ];

                return ApiResponseClass::validationError(
                    ['audience' => [$messages[$result['reason']] ?? 'Não foi possível enviar os e-mails.']],
                    $messages[$result['reason']] ?? 'Não foi possível enviar os e-mails.'
                );
            }

            return ApiResponseClass::sendResponse([
                'queued' => $result['queued'],
                'failed' => $result['failed'],
                'total' => $result['total'],
                'tenant_total' => $result['tenant_total'],
                'newsletter_total' => $result['newsletter_total'],
            ], "{$result['queued']} e-mail(s) enfileirado(s) para envio com sucesso!");
        } catch (\Exception $e) {
            Log::error('AdminEmailController: Erro ao processar envio de emails em massa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => $admin->id,
            ]);

            return ApiResponseClass::throw($e, 'Erro ao processar envio de e-mails');
        }
    }

    public function newsletterSubscribers(): JsonResponse
    {
        try {
            return ApiResponseClass::sendResponse([
                'stats' => $this->emailService->getNewsletterStats(),
                'subscribers' => $this->emailService->getNewsletterSubscribers(),
            ], 'Inscritos recuperados com sucesso.');
        } catch (\Exception $e) {
            return ApiResponseClass::throw($e, 'Erro ao buscar inscritos do informativo');
        }
    }

    public function emailHistory(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 50);

            return ApiResponseClass::sendResponse(
                $this->emailService->getEmailHistory($limit),
                'Histórico de e-mails recuperado com sucesso.'
            );
        } catch (\Exception $e) {
            Log::error('AdminEmailController: Erro ao buscar histórico de emails', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponseClass::throw($e, 'Erro ao buscar histórico de e-mails');
        }
    }
}
