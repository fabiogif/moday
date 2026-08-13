<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\AbcCurveService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbcSuppliersReportController extends Controller
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

            $rows = PurchaseOrder::where('tenant_id', $tenantId)
                ->whereIn('status', ['confirmado', 'recebido'])
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('supplier_id')
                ->selectRaw('supplier_id, SUM(total) as volume, COUNT(*) as order_count, AVG(DATEDIFF(received_at, expected_delivery)) as avg_delay_days')
                ->groupBy('supplier_id')
                ->orderByDesc('volume')
                ->with('supplier:id,name,cnpj')
                ->get();

            $totalVolume = $rows->sum('volume');

            $classified = $this->abcCurveService->classify(
                $rows,
                (float) $totalVolume,
                'volume',
                fn ($row, $percent, $cumulative, $class) => [
                    'supplier_id'        => $row->supplier_id,
                    'supplier_name'      => $row->supplier?->name ?? 'Não identificado',
                    'cnpj'               => $row->supplier?->cnpj,
                    'order_count'        => $row->order_count,
                    'volume'             => round($row->volume, 2),
                    'volume_percent'     => $percent,
                    'cumulative_percent' => $cumulative,
                    'avg_delay_days'     => $row->avg_delay_days !== null ? round($row->avg_delay_days, 1) : null,
                    'class'              => $class,
                ]
            );

            return ApiResponseClass::sendResponse([
                'period'          => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'total_volume'    => round($totalVolume, 2),
                'total_suppliers' => $rows->count(),
                'summary'         => $this->abcCurveService->buildSummary($classified, 'volume'),
                'items'           => $classified->values(),
            ], 'Curva ABC de fornecedores gerada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar ABC de fornecedores');
        }
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data  = json_decode($this->index($request)->getContent(), true)['data'] ?? [];
        $items = $data['items'] ?? [];

        $csv = "Classe,Fornecedor,CNPJ,Pedidos,Volume,Atraso Médio (dias),% Acumulado\n";
        foreach ($items as $item) {
            $csv .= implode(',', [
                $item['class'],
                "\"{$item['supplier_name']}\"",
                $item['cnpj'] ?? '',
                $item['order_count'],
                $item['volume'],
                $item['avg_delay_days'] ?? 0,
                $item['cumulative_percent'],
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="abc-fornecedores.csv"',
        ]);
    }
}
