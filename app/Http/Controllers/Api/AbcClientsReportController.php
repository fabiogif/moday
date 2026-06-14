<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\SaleOrder;
use App\Services\AbcCurveService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbcClientsReportController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly AbcCurveService $abcCurveService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            [$start, $end] = $this->abcCurveService->parseDateRange(
                $request->get('start'),
                $request->get('end')
            );

            $rows = SaleOrder::where('tenant_id', $tenantId)
                ->whereIn('status', ['faturado', 'entregue'])
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('client_id')
                ->selectRaw('client_id, SUM(total) as revenue, COUNT(*) as order_count')
                ->groupBy('client_id')
                ->orderByDesc('revenue')
                ->with('client:id,name,company_name,trade_name,cnpj,cpf')
                ->get();

            $totalRevenue = $rows->sum('revenue');

            $classified = $this->abcCurveService->classify(
                $rows,
                (float) $totalRevenue,
                'revenue',
                fn ($row, $percent, $cumulative, $class) => [
                    'client_id'          => $row->client_id,
                    'client_name'        => $row->client?->company_name ?? $row->client?->trade_name ?? $row->client?->name ?? 'Não identificado',
                    'document'           => $row->client?->cnpj ?? $row->client?->cpf,
                    'order_count'        => $row->order_count,
                    'revenue'            => round($row->revenue, 2),
                    'revenue_percent'    => $percent,
                    'cumulative_percent' => $cumulative,
                    'class'              => $class,
                ]
            );

            return ApiResponseClass::sendResponse([
                'period'        => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'total_revenue' => round($totalRevenue, 2),
                'total_clients' => $rows->count(),
                'summary'       => $this->abcCurveService->buildSummary($classified, 'revenue'),
                'items'         => $classified->values(),
            ], 'Curva ABC de clientes gerada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar ABC de clientes');
        }
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data  = json_decode($this->index($request)->getContent(), true)['data'] ?? [];
        $items = $data['items'] ?? [];

        $csv = "Classe,Cliente,CNPJ/CPF,Pedidos,Faturamento,% Acumulado\n";
        foreach ($items as $item) {
            $csv .= implode(',', [
                $item['class'],
                "\"{$item['client_name']}\"",
                $item['document'] ?? '',
                $item['order_count'],
                $item['revenue'],
                $item['cumulative_percent'],
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="abc-clientes.csv"',
        ]);
    }
}
