<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Logistics\ShipmentPdfService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class ShipmentPdfController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly ShipmentPdfService $shipmentPdfService,
    ) {}

    public function show(int $id): Response
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            return $this->shipmentPdfService->streamForTenant($tenantId, $id);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Romaneio não encontrado'], 404);
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF: ' . $ex->getMessage(),
            ], 500);
        }
    }
}
