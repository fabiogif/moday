<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Api\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    public function __construct(protected DashboardService $dashboardService)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Dados do dashboard",
     *     description="Retorna dados completos do dashboard com estatísticas e métricas",
     *     tags={"Dashboard"},
     *     @OA\Response(
     *         response=200,
     *         description="Dados do dashboard carregados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="overview", type="object",
     *                     @OA\Property(property="total_clients", type="integer", example=150),
     *                     @OA\Property(property="total_products", type="integer", example=50),
     *                     @OA\Property(property="total_orders", type="integer", example=300),
     *                     @OA\Property(property="total_categories", type="integer", example=10),
     *                     @OA\Property(property="total_tables", type="integer", example=5)
     *                 ),
     *                 @OA\Property(property="revenue", type="object",
     *                     @OA\Property(property="current_month", type="number", format="float", example=15000.50),
     *                     @OA\Property(property="previous_month", type="number", format="float", example=12000.00),
     *                     @OA\Property(property="growth", type="number", format="float", example=25.0)
     *                 ),
     *                 @OA\Property(property="orders", type="object",
     *                     @OA\Property(property="current_month", type="integer", example=150),
     *                     @OA\Property(property="previous_month", type="integer", example=120),
     *                     @OA\Property(property="growth", type="number", format="float", example=25.0),
     *                     @OA\Property(property="by_status", type="object",
     *                         @OA\Property(property="Em Preparo", type="integer", example=50),
     *                         @OA\Property(property="Pronto", type="integer", example=30),
     *                         @OA\Property(property="Entregue", type="integer", example=80),
     *                         @OA\Property(property="Cancelado", type="integer", example=20)
     *                     )
     *                 ),
     *                 @OA\Property(property="top_products", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Pizza Margherita"),
     *                     @OA\Property(property="count", type="integer", example=25),
     *                     @OA\Property(property="revenue", type="number", format="float", example=1250.00)
     *                 )),
     *                 @OA\Property(property="recent_orders", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="identify", type="string", example="abc12345"),
     *                     @OA\Property(property="total", type="number", format="float", example=50.00),
     *                     @OA\Property(property="status", type="string", example="Em Preparo"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="period", type="object",
     *                     @OA\Property(property="current_month", type="string", example="2024-01"),
     *                     @OA\Property(property="previous_month", type="string", example="2023-12")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Dados do dashboard carregados com sucesso")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $dashboardData = $this->dashboardService->getDashboardData($user->tenant_id);
            return ApiResponseClass::sendResponse($dashboardData, 'Dados do dashboard carregados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar dados do dashboard');
        }
    }
}
