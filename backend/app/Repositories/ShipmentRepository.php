<?php

namespace App\Repositories;

use App\Models\SaleOrder;
use App\Models\Shipment;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShipmentRepository implements ShipmentRepositoryInterface
{
    public function findForTenant(int $tenantId, int $id, array $with = []): ?Shipment
    {
        return Shipment::forTenant($tenantId)->with($with)->find($id);
    }

    public function findForTenantOrFail(int $tenantId, int $id, array $with = []): Shipment
    {
        return Shipment::forTenant($tenantId)->with($with)->findOrFail($id);
    }

    public function update(Shipment $shipment, array $data): Shipment
    {
        $shipment->update($data);

        return $shipment->fresh() ?? $shipment;
    }

    public function updatePivotDeliveryWindow(
        int $shipmentId,
        int $saleOrderId,
        string $windowStart,
        string $windowEnd,
    ): void {
        DB::table('shipment_sale_order')
            ->where('shipment_id', $shipmentId)
            ->where('sale_order_id', $saleOrderId)
            ->update([
                'delivery_window_start' => $windowStart,
                'delivery_window_end' => $windowEnd,
            ]);
    }

    public function updatePivotDeliverySequences(int $shipmentId, array $sequences): void
    {
        foreach ($sequences as $item) {
            DB::table('shipment_sale_order')
                ->where('shipment_id', $shipmentId)
                ->where('sale_order_id', $item['sale_order_id'])
                ->update(['delivery_sequence' => $item['sequence']]);
        }
    }

    public function hasSaleOrder(int $shipmentId, int $saleOrderId): bool
    {
        return DB::table('shipment_sale_order')
            ->where('shipment_id', $shipmentId)
            ->where('sale_order_id', $saleOrderId)
            ->exists();
    }

    public function persistRoute(
        Shipment $shipment,
        array $routeSummary,
        float $estimatedKm,
        ?int $durationMinutes,
        ?string $polyline,
        ?string $region,
        string $orderSource = 'system',
    ): Shipment {
        return DB::transaction(function () use (
            $shipment,
            $routeSummary,
            $estimatedKm,
            $durationMinutes,
            $polyline,
            $region,
            $orderSource,
        ) {
            $sequences = array_map(
                static fn (array $item): array => [
                    'sale_order_id' => (int) $item['sale_order_id'],
                    'sequence' => (int) $item['sequence'],
                ],
                $routeSummary,
            );

            $this->updatePivotDeliverySequences($shipment->id, $sequences);

            $shipment->update([
                'optimized_route' => $routeSummary,
                'route_order_source' => $orderSource === 'manual' ? 'manual' : 'system',
                'estimated_km' => $estimatedKm,
                'estimated_duration_minutes' => $durationMinutes,
                'route_polyline' => $polyline,
                'region' => $region,
            ]);

            return $shipment->fresh() ?? $shipment;
        });
    }

    public function updateSaleOrderCoordinates(SaleOrder $order, float $lat, float $lng): SaleOrder
    {
        $order->update([
            'shipping_latitude' => $lat,
            'shipping_longitude' => $lng,
        ]);

        return $order->fresh() ?? $order;
    }

    public function listOrdersForRouteGrouping(int $tenantId, array $saleOrderIds = []): Collection
    {
        $query = SaleOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['faturado', 'separacao'])
            ->whereNotNull('shipping_zipcode')
            ->select(
                'id',
                'identify',
                'client_id',
                'total',
                'shipping_zipcode',
                'shipping_city',
                'shipping_state',
                'shipping_address',
                'estimated_delivery',
                'delivery_window_start',
                'delivery_window_end',
            )
            ->with('client:id,name,company_name,trade_name,phone');

        if ($saleOrderIds !== []) {
            $query->whereIn('id', $saleOrderIds);
        }

        return $query->orderBy('shipping_zipcode')->get();
    }
}
