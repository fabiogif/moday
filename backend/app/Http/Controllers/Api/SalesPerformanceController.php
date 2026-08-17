<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\SalesPerformanceRequest;
use App\Services\SalesPerformanceService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;

class SalesPerformanceController extends Controller
{
    public function __construct(
        protected SalesPerformanceService $salesPerformanceService,
        protected AuthTenantService $authTenantService
    ) {}

    /**
     * Get sales performance data
     *
     * @OA\Get(
     *     path="/api/sales-performance",
     *     summary="Obter dados de desempenho/vendas",
     *     description="Retorna indicadores de desempenho/vendas para a loja, incluindo total de vendas, valor total, ticket médio, novos clientes, horários e dias com mais vendas, e formas de pagamento mais usadas",
     *     tags={"Sales Performance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Data de início (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Data de fim (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Número de dias (padrão: 7)",
     *         required=false,
     *         @OA\Schema(type="integer", default=7, minimum=1, maximum=365)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados de desempenho/vendas carregados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="period", type="object",
     *                     @OA\Property(property="start_date", type="string", example="2024-01-01 00:00:00"),
     *                     @OA\Property(property="end_date", type="string", example="2024-01-07 23:59:59"),
     *                     @OA\Property(property="days", type="integer", example=7)
     *                 ),
     *                 @OA\Property(property="indicators", type="object",
     *                     @OA\Property(property="total_sales", type="object",
     *                         @OA\Property(property="current", type="integer", example=150),
     *                         @OA\Property(property="previous", type="integer", example=120),
     *                         @OA\Property(property="growth", type="number", format="float", example=25.0)
     *                     ),
     *                     @OA\Property(property="total_sales_value", type="object",
     *                         @OA\Property(property="current", type="number", format="float", example=15000.50),
     *                         @OA\Property(property="previous", type="number", format="float", example=12000.00),
     *                         @OA\Property(property="growth", type="number", format="float", example=25.0)
     *                     ),
     *                     @OA\Property(property="average_ticket", type="object",
     *                         @OA\Property(property="current", type="number", format="float", example=100.00),
     *                         @OA\Property(property="previous", type="number", format="float", example=100.00),
     *                         @OA\Property(property="growth", type="number", format="float", example=0.0)
     *                     ),
     *                     @OA\Property(property="new_clients", type="object",
     *                         @OA\Property(property="current", type="integer", example=30),
     *                         @OA\Property(property="previous", type="integer", example=25),
     *                         @OA\Property(property="growth", type="number", format="float", example=20.0)
     *                     )
     *                 ),
     *                 @OA\Property(property="sales_by_hour", type="array", @OA\Items(
     *                     @OA\Property(property="hour", type="integer", example=14),
     *                     @OA\Property(property="count", type="integer", example=25),
     *                     @OA\Property(property="hour_label", type="string", example="14:00")
     *                 )),
     *                 @OA\Property(property="best_hour", type="object",
     *                     @OA\Property(property="hour", type="integer", example=14),
     *                     @OA\Property(property="count", type="integer", example=25),
     *                     @OA\Property(property="hour_label", type="string", example="14:00")
     *                 ),
     *                 @OA\Property(property="sales_by_day", type="array", @OA\Items(
     *                     @OA\Property(property="day_of_week", type="integer", example=1),
     *                     @OA\Property(property="day_name", type="string", example="Segunda-feira"),
     *                     @OA\Property(property="count", type="integer", example=30)
     *                 )),
     *                 @OA\Property(property="best_day", type="object",
     *                     @OA\Property(property="day_of_week", type="integer", example=1),
     *                     @OA\Property(property="day_name", type="string", example="Segunda-feira"),
     *                     @OA\Property(property="count", type="integer", example=30)
     *                 ),
     *                 @OA\Property(property="sales_by_payment_method", type="array", @OA\Items(
     *                     @OA\Property(property="payment_method", type="string", example="Crédito"),
     *                     @OA\Property(property="count", type="integer", example=75),
     *                     @OA\Property(property="total_value", type="number", format="float", example=7500.00)
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Dados de desempenho/vendas carregados com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acesso negado"
     *     )
     * )
     */
    public function index(SalesPerformanceRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $days = (int) $request->input('days', 7);

            $data = $this->salesPerformanceService->getSalesPerformance(
                $tenantId,
                $startDate,
                $endDate,
                $days
            );

            return ApiResponseClass::sendResponse($data, 'Dados de desempenho/vendas carregados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar dados de desempenho/vendas');
        }
    }

    /**
     * Export sales performance data
     *
     * @OA\Get(
     *     path="/api/sales-performance/export",
     *     summary="Exportar dados de desempenho/vendas",
     *     description="Exporta dados de desempenho/vendas em formato JSON",
     *     tags={"Sales Performance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Data de início (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Data de fim (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Número de dias (padrão: 7)",
     *         required=false,
     *         @OA\Schema(type="integer", default=7, minimum=1, maximum=365)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados exportados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function export(SalesPerformanceRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $days = (int) $request->input('days', 7);

            $data = $this->salesPerformanceService->exportSalesPerformance(
                $tenantId,
                $startDate,
                $endDate,
                $days
            );

            return ApiResponseClass::sendResponse($data, 'Dados exportados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao exportar dados de desempenho/vendas');
        }
    }

    /**
     * Refresh sales performance data (invalidate cache)
     *
     * @OA\Post(
     *     path="/api/sales-performance/refresh",
     *     summary="Atualizar dados de desempenho/vendas",
     *     description="Invalida o cache e força a atualização dos dados de desempenho/vendas",
     *     tags={"Sales Performance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados atualizados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dados atualizados com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function refresh(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            
            $this->salesPerformanceService->invalidateCache($tenantId);

            return ApiResponseClass::sendResponse([], 'Dados atualizados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar dados de desempenho/vendas');
        }
    }
}

