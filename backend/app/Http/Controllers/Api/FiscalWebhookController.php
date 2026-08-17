<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\FiscalException;
use App\Http\Controllers\Controller;
use App\Services\Fiscal\FiscalIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalWebhookController extends Controller
{
    public function __construct(private readonly FiscalIntegrationService $fiscalIntegrationService) {}

    public function __invoke(Request $request, int $tenantId): JsonResponse
    {
        try {
            $event = $this->fiscalIntegrationService->processWebhook($tenantId, $request);

            return response()->json([
                'success' => true,
                'data'    => ['event_id' => $event->id],
                'message' => 'Webhook fiscal processado',
            ], 202);
        } catch (FiscalException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 403);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao processar webhook fiscal');
        }
    }
}
