<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\AccountReceivable;
use App\Models\SaleOrder;
use App\Services\AuthTenantService;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class B2BPaymentLinkController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly MercadoPagoService $mpService,
    ) {}

    /** POST /api/sale-orders/{id}/payment-link */
    public function generate(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $saleOrder = SaleOrder::where('tenant_id', $tenantId)
                ->with('client')
                ->findOrFail($id);

            if (!in_array($saleOrder->status, ['aprovado', 'faturado', 'entregue'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link de pagamento disponível apenas para pedidos aprovados ou faturados.',
                ], 422);
            }

            $clientEmail = $saleOrder->client?->email ?? ($request->get('payer_email') ?? '');

            if (empty($clientEmail)) {
                return response()->json([
                    'success' => false,
                    'message' => 'O cliente não possui e-mail cadastrado. Informe o e-mail do pagador.',
                ], 422);
            }

            $reference   = "sale_order_{$id}_tenant_{$tenantId}";
            $description = "Pedido #{$saleOrder->identify} — " . ($saleOrder->client?->company_name ?? 'Cliente');

            $result = $this->mpService->createPaymentLink(
                amount:            (float) $saleOrder->total,
                description:       $description,
                externalReference: $reference,
                payerEmail:        $clientEmail,
            );

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['error']], 422);
            }

            // Save payment link in the related accounts_receivable
            AccountReceivable::where('sale_order_id', $id)
                ->whereIn('status', ['pendente', 'vencido', 'parcial'])
                ->update([
                    'mp_preference_id'     => $result['preference_id'],
                    'mp_payment_link'      => $result['init_point'],
                    'mp_link_generated_at' => now(),
                ]);

            return ApiResponseClass::sendResponse([
                'preference_id' => $result['preference_id'],
                'payment_link'  => $result['init_point'],
                'sandbox_url'   => $result['sandbox_url'],
                'amount'        => $saleOrder->total,
                'description'   => $description,
            ], 'Link de pagamento gerado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar link de pagamento');
        }
    }

    /** GET /api/sale-orders/{id}/payment-link/status */
    public function status(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            SaleOrder::where('tenant_id', $tenantId)->findOrFail($id);

            $ar = AccountReceivable::where('sale_order_id', $id)
                ->whereNotNull('mp_preference_id')
                ->latest()
                ->first();

            if (!$ar) {
                return response()->json(['success' => false, 'message' => 'Link de pagamento não encontrado'], 404);
            }

            return ApiResponseClass::sendResponse([
                'payment_link'        => $ar->mp_payment_link,
                'mp_payment_id'       => $ar->mp_payment_id,
                'link_generated_at'   => $ar->mp_link_generated_at,
                'payment_status'      => $ar->status,
                'amount'              => $ar->amount,
                'amount_received'     => $ar->amount_received,
            ], 'Status do link de pagamento', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar status do link');
        }
    }
}
