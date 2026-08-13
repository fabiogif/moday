<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\NewsletterSubscribeRequest;
use App\Services\NewsletterService;
use Illuminate\Support\Facades\Log;

class NewsletterApiController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {}

    public function subscribe(NewsletterSubscribeRequest $request)
    {
        try {
            $subscriber = $this->newsletterService->subscribe($request->validated('email'));

            return ApiResponseClass::sendResponse(
                [
                    'email' => $subscriber->email,
                    'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                ],
                'Inscrição realizada com sucesso! Você receberá novidades em breve.',
                201
            );
        } catch (\Exception $e) {
            Log::error('NewsletterApiController: erro ao inscrever e-mail', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return ApiResponseClass::throw($e, 'Não foi possível concluir a inscrição. Tente novamente.');
        }
    }
}
