<?php

namespace App\Services\Logistics;

use App\Exceptions\StockException;
use App\Models\Driver;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function create(int $tenantId, int $userId, array $data): Shipment
    {
        return DB::transaction(function () use ($tenantId, $userId, $data) {
            $references = $data['sale_order_ids'] ?? [];
            $resolvedIds = [];

            foreach ($references as $reference) {
                $order = $this->resolveSaleOrder($tenantId, $reference);
                if (!in_array($order->status, ['faturado', 'separacao'], true)) {
                    throw StockException::invalidMovement(
                        "Pedido {$order->identify} não está pronto para expedição (status: {$order->status})."
                    );
                }
                $resolvedIds[] = $order->id;
            }

            $resolvedIds = array_values(array_unique($resolvedIds));

            // Resolve vehicle and driver snapshots for historical record
            $vehicleId   = $data['vehicle_id'] ?? null;
            $driverId    = $data['driver_id'] ?? null;
            $vehiclePlate = $data['vehicle_plate'] ?? null;
            $driverName   = $data['driver_name'] ?? null;

            if ($vehicleId) {
                $vehicle = Vehicle::forTenant($tenantId)->find($vehicleId);
                if ($vehicle) {
                    $vehiclePlate = $vehicle->plate;
                }
            }

            if ($driverId) {
                $driver = Driver::forTenant($tenantId)->find($driverId);
                if ($driver) {
                    $driverName = $driver->name;
                }
            }

            $shipment = Shipment::create([
                'tenant_id'     => $tenantId,
                'carrier_id'    => $data['carrier_id'] ?? null,
                'vehicle_id'    => $vehicleId,
                'driver_id'     => $driverId,
                'route_name'    => $data['route_name'] ?? null,
                'driver_name'   => $driverName,
                'vehicle_plate' => $vehiclePlate,
                'notes'         => $data['notes'] ?? null,
                'created_by'    => $userId,
                'status'        => 'draft',
            ]);

            if (!empty($resolvedIds)) {
                $syncData = [];
                $orders = SaleOrder::whereIn('id', $resolvedIds)->get(['id', 'shipping_zipcode', 'delivery_window_start', 'delivery_window_end']);
                foreach ($orders as $order) {
                    $syncData[$order->id] = [
                        'delivery_zipcode' => $order->shipping_zipcode,
                        'delivery_window_start' => $order->delivery_window_start,
                        'delivery_window_end' => $order->delivery_window_end,
                    ];
                }
                $shipment->saleOrders()->sync($syncData);

                // Calculate total weight and volume from order items
                $totalWeightKg = 0.0;
                $totalVolumeM3 = 0.0;
                $items = SaleOrderItem::with('product')
                    ->whereIn('sale_order_id', $resolvedIds)
                    ->get();
                foreach ($items as $item) {
                    $product = $item->product;
                    if (!$product) continue;
                    $qty = (float) $item->quantity;
                    if ((float) $product->weight > 0) {
                        $totalWeightKg += $qty * (float) $product->weight;
                    }
                    if ((float) $product->height > 0 && (float) $product->width > 0 && (float) $product->depth > 0) {
                        $volPerUnit = ((float) $product->height * (float) $product->width * (float) $product->depth) / 1_000_000;
                        $totalVolumeM3 += $qty * $volPerUnit;
                    }
                }
                $shipment->update([
                    'total_weight_kg' => $totalWeightKg > 0 ? round($totalWeightKg, 3) : null,
                    'total_volume_m3' => $totalVolumeM3 > 0 ? round($totalVolumeM3, 4) : null,
                ]);
            }

            $this->auditService->log($tenantId, $userId, 'shipment.created', 'shipment', $shipment->id, [
                'sale_order_ids' => $resolvedIds,
            ]);

            return $shipment->load(['carrier', 'saleOrders']);
        });
    }

    /**
     * Pedidos elegíveis para romaneio (faturado/separação, ainda não em rota ativa).
     */
    public function listPendingOrders(int $tenantId): \Illuminate\Support\Collection
    {
        $assignedIds = DB::table('shipment_sale_order')
            ->join('shipments', 'shipments.id', '=', 'shipment_sale_order.shipment_id')
            ->where('shipments.tenant_id', $tenantId)
            ->whereIn('shipments.status', ['draft', 'dispatched'])
            ->pluck('shipment_sale_order.sale_order_id');

        return SaleOrder::forTenant($tenantId)
            ->whereIn('status', ['faturado', 'separacao'])
            ->when($assignedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $assignedIds))
            ->with('client:id,name,company_name,trade_name')
            ->orderByDesc('id')
            ->get([
                'id', 'identify', 'status', 'total',
                'shipping_city', 'shipping_state', 'shipping_zipcode', 'client_id',
            ]);
    }

    private function resolveSaleOrder(int $tenantId, mixed $reference): SaleOrder
    {
        if (is_numeric($reference)) {
            $order = SaleOrder::forTenant($tenantId)->find((int) $reference);
            if ($order) {
                return $order;
            }
        }

        $identify = trim((string) $reference);
        $order = SaleOrder::forTenant($tenantId)->where('identify', $identify)->first();
        if ($order) {
            return $order;
        }

        throw StockException::invalidMovement(
            "Pedido \"{$reference}\" não encontrado. Selecione na lista ou informe o código (ex: PV-EE3JQR)."
        );
    }

    public function dispatch(Shipment $shipment, int $userId): Shipment
    {
        if ($shipment->status !== 'draft') {
            throw StockException::invalidMovement('Romaneio já foi expedido.');
        }

        $shipment->update([
            'status'     => 'dispatched',
            'shipped_at' => now(),
        ]);

        $this->auditService->log($shipment->tenant_id, $userId, 'shipment.dispatched', 'shipment', $shipment->id);

        return $shipment->fresh(['carrier', 'saleOrders']);
    }

    public function deliver(Shipment $shipment, int $userId, ?string $podReference = null): Shipment
    {
        if ($shipment->status !== 'dispatched') {
            throw StockException::invalidMovement('Romaneio precisa estar em trânsito para entrega.');
        }

        return DB::transaction(function () use ($shipment, $userId, $podReference) {
            $shipment->update([
                'status'        => 'delivered',
                'delivered_at'  => now(),
                'pod_reference' => $podReference,
            ]);

            foreach ($shipment->saleOrders as $order) {
                if ($order->status === 'faturado') {
                    $order->update(['status' => 'entregue', 'delivered_at' => now()]);
                }
            }

            $this->auditService->log($shipment->tenant_id, $userId, 'shipment.delivered', 'shipment', $shipment->id, [
                'pod_reference' => $podReference,
            ]);

            return $shipment->fresh(['carrier', 'saleOrders']);
        });
    }
}
