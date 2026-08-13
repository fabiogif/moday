<?php

namespace App\Repositories\Contracts;

use App\Models\SaleOrder;
use App\Models\Shipment;
use Illuminate\Support\Collection;

interface ShipmentRepositoryInterface
{
    public function findForTenant(int $tenantId, int $id, array $with = []): ?Shipment;

    public function findForTenantOrFail(int $tenantId, int $id, array $with = []): Shipment;

    public function update(Shipment $shipment, array $data): Shipment;

    public function updatePivotDeliveryWindow(
        int $shipmentId,
        int $saleOrderId,
        string $windowStart,
        string $windowEnd,
    ): void;

    /**
     * @param  array<int, array{sale_order_id: int, sequence: int}>  $sequences
     */
    public function updatePivotDeliverySequences(int $shipmentId, array $sequences): void;

    public function hasSaleOrder(int $shipmentId, int $saleOrderId): bool;

    /**
     * Persist optimized route metrics and pivot sequences in one transaction.
     *
     * @param  array<int, array<string, mixed>>  $routeSummary
     */
    public function persistRoute(
        Shipment $shipment,
        array $routeSummary,
        float $estimatedKm,
        ?int $durationMinutes,
        ?string $polyline,
        ?string $region,
        string $orderSource = 'system',
    ): Shipment;

    public function updateSaleOrderCoordinates(SaleOrder $order, float $lat, float $lng): SaleOrder;

    /**
     * Pending sale orders used for region grouping suggestions.
     *
     * @param  array<int, int>  $saleOrderIds
     * @return Collection<int, SaleOrder>
     */
    public function listOrdersForRouteGrouping(int $tenantId, array $saleOrderIds = []): Collection;
}
