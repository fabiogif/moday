<?php

namespace App\Http\Controllers\Api\Integrations\Ifood;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\Integrations\Ifood\IfoodOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class IfoodOAuthController extends Controller
{
    public function __construct(
        private readonly IfoodOAuthService $oauthService,
    ) {
    }

    public function requestUserCode(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $data = $this->oauthService->requestUserCode($tenantId);

        return ApiResponseClass::sendResponse(
            [
                'user_code' => $data['user_code'],
                'verification_url' => $data['verification_url'],
                'verification_url_complete' => $data['verification_url_complete'],
                'expires_in' => $data['expires_in'],
                'expires_at' => $data['expires_at'],
            ],
            'Código de autorização iFood gerado com sucesso. Solicite ao merchant que conclua a autorização no portal.',
            Response::HTTP_OK
        );
    }

    private function resolveTenantId(Request $request): int
    {
        $tenantId = $request->integer('tenant_id')
            ?? $request->header('X-Tenant-Id')
            ?? Auth::user()?->tenant_id;

        if (!$tenantId) {
            throw ValidationException::withMessages([
                'tenant_id' => 'tenant_id não informado.',
            ]);
        }

        return (int) $tenantId;
    }
}

