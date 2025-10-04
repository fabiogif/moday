<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Api\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderStatsApiController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/order/stats",
     *     summary="Estatísticas de pedidos",
     *     description="Retorna estatísticas detalhadas dos pedidos comparando com o mês anterior, incluindo todos os status",
     *     tags={"Pedido"},
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas carregadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_orders", type="object",
     *                     @OA\Property(property="current", type="integer", example=150),
     *                     @OA\Property(property="previous", type="integer", example=120),
     *                     @OA\Property(property="growth", type="number", format="float", example=25.0)
     *                 ),
     *                 @OA\Property(property="total_revenue", type="object",
     *                     @OA\Property(property="current", type="number", format="float", example=15000.50),
     *                     @OA\Property(property="previous", type="number", format="float", example=12000.00),
     *                     @OA\Property(property="growth", type="number", format="float", example=25.0)
     *                 ),
     *                 @OA\Property(property="average_order_value", type="object",
     *                     @OA\Property(property="current", type="number", format="float", example=100.00),
     *                     @OA\Property(property="previous", type="number", format="float", example=100.00),
     *                     @OA\Property(property="growth", type="number", format="float", example=0.0)
     *                 ),
     *                 @OA\Property(property="pending_orders", type="object",
     *                     @OA\Property(property="current", type="integer", example=30),
     *                     @OA\Property(property="previous", type="integer", example=25),
     *                     @OA\Property(property="growth", type="number", format="float", example=20.0)
     *                 ),
     *                 @OA\Property(property="paid_orders", type="object",
     *                     @OA\Property(property="current", type="integer", example=100),
     *                     @OA\Property(property="previous", type="integer", example=80),
     *                     @OA\Property(property="growth", type="number", format="float", example=25.0)
     *                 ),
     *                 @OA\Property(property="delivered_orders", type="object",
     *                     @OA\Property(property="current", type="integer", example=90),
     *                     @OA\Property(property="previous", type="integer", example=75),
     *                     @OA\Property(property="growth", type="number", format="float", example=20.0)
     *                 ),
     *                 @OA\Property(property="in_progress_orders", type="object",
     *                     @OA\Property(property="current", type="integer", example=15),
     *                     @OA\Property(property="previous", type="integer", example=10),
     *                     @OA\Property(property="growth", type="number", format="float", example=50.0)
     *                 ),
     *                 @OA\Property(property="canceled_orders", type="object",
     *                     @OA\Property(property="current", type="integer", example=5),
     *                     @OA\Property(property="previous", type="integer", example=3),
     *                     @OA\Property(property="growth", type="number", format="float", example=66.7)
     *                 ),
     *                 @OA\Property(property="orders_by_status", type="object",
     *                     @OA\Property(property="Pendente", type="integer", example=30),
     *                     @OA\Property(property="Em Preparo", type="integer", example=15),
     *                     @OA\Property(property="Pago", type="integer", example=100),
     *                     @OA\Property(property="Entregue", type="integer", example=90),
     *                     @OA\Property(property="Cancelado", type="integer", example=5)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Estatísticas carregadas com sucesso")
     *         )
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $stats = $this->orderService->getOrderStats($user->tenant_id);
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }
}
