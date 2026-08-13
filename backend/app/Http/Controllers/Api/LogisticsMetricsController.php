<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogisticsMetricsController extends Controller
{
    public function __construct(private readonly AuthTenantService $authTenantService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $period = $request->get('period', now()->format('Y-m'));

            if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
                $period = now()->format('Y-m');
            }

            [$year, $month] = explode('-', $period);
            $from = Carbon::create((int) $year, (int) $month)->startOfMonth();
            $to   = Carbon::create((int) $year, (int) $month)->endOfMonth();

            $shipments = Shipment::forTenant($tenantId)
                ->whereBetween('created_at', [$from, $to])
                ->with(['saleOrders', 'occurrences'])
                ->get();

            $total      = $shipments->count();
            $delivered  = $shipments->where('status', 'delivered')->count();
            $inTransit  = $shipments->where('status', 'dispatched')->count();
            $draft      = $shipments->where('status', 'draft')->count();

            $totalKm       = (float) $shipments->sum('estimated_km');
            $totalWeightKg = (float) $shipments->sum('total_weight_kg');

            $occurrencesCount = (int) $shipments->sum(fn ($s) => $s->occurrences->count());

            // Average delivery time (shipped_at → delivered_at)
            $withTimes = $shipments->filter(
                fn ($s) => $s->status === 'delivered' && $s->shipped_at && $s->delivered_at
            );
            $avgDeliveryMinutes = $withTimes->isNotEmpty()
                ? (int) round($withTimes->avg(fn ($s) => $s->shipped_at->diffInMinutes($s->delivered_at)))
                : null;

            // Refuse / partial rate from POD data
            $totalPodOrders = DB::table('shipment_sale_order')
                ->join('shipments', 'shipments.id', '=', 'shipment_sale_order.shipment_id')
                ->where('shipments.tenant_id', $tenantId)
                ->whereBetween('shipments.created_at', [$from, $to])
                ->whereNotNull('shipment_sale_order.pod_status')
                ->count();

            $problemOrders = DB::table('shipment_sale_order')
                ->join('shipments', 'shipments.id', '=', 'shipment_sale_order.shipment_id')
                ->where('shipments.tenant_id', $tenantId)
                ->whereBetween('shipments.created_at', [$from, $to])
                ->whereIn('shipment_sale_order.pod_status', ['refused', 'partial'])
                ->count();

            $returnRate = $totalPodOrders > 0 ? round($problemOrders / $totalPodOrders, 3) : 0.0;

            // Daily deliveries chart data
            $dailyDeliveries = DB::table('shipments')
                ->where('tenant_id', $tenantId)
                ->where('status', 'delivered')
                ->whereNotNull('delivered_at')
                ->whereBetween('delivered_at', [$from, $to])
                ->select(DB::raw('DATE(delivered_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => [
                    'date'  => $row->date,
                    'label' => Carbon::parse($row->date)->format('d/m'),
                    'count' => (int) $row->count,
                ])
                ->values();

            // Shipment list for table
            $list = $shipments->sortByDesc('created_at')->map(fn ($s) => [
                'id'              => $s->id,
                'identify'        => $s->identify,
                'status'          => $s->status,
                'driver_name'     => $s->driver_name,
                'vehicle_plate'   => $s->vehicle_plate,
                'estimated_km'    => $s->estimated_km ? (float) $s->estimated_km : null,
                'total_weight_kg' => $s->total_weight_kg ? (float) $s->total_weight_kg : null,
                'stops'           => $s->saleOrders->count(),
                'occurrences'     => $s->occurrences->count(),
                'shipped_at'      => $s->shipped_at?->format('d/m/Y H:i'),
                'delivered_at'    => $s->delivered_at?->format('d/m/Y H:i'),
            ])->values();

            return ApiResponseClass::sendResponse([
                'period'              => $period,
                'shipments_total'     => $total,
                'delivered_count'     => $delivered,
                'in_transit_count'    => $inTransit,
                'draft_count'         => $draft,
                'occurrences_count'   => $occurrencesCount,
                'total_km'            => round($totalKm, 2),
                'total_weight_kg'     => round($totalWeightKg, 1),
                'avg_delivery_minutes' => $avgDeliveryMinutes,
                'return_rate'         => $returnRate,
                'daily_deliveries'    => $dailyDeliveries,
                'shipments'           => $list,
            ], 'Métricas logísticas recuperadas', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao recuperar métricas logísticas');
        }
    }
}
