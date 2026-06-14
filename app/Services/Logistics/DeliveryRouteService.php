<?php

namespace App\Services\Logistics;

use App\Models\SaleOrder;
use App\Models\Shipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryRouteService
{
    // Brazil: first 3 digits of CEP define macro-region (e.g. 010 = Centro SP)
    private const CEP_REGION_PREFIX_LENGTH = 3;

    // Average km between consecutive stops within a region (conservative estimate)
    private const AVG_KM_PER_STOP = 3.0;

    // Default vehicle parameters for cost calculation
    private const DEFAULT_KM_PER_LITER    = 10.0;
    private const DEFAULT_FUEL_PRICE      = 6.50; // BRL/L
    private const DEFAULT_DRIVER_COST_KM  = 0.50; // BRL/km (toll + driver)

    /**
     * Groups pending sale orders by delivery region (CEP prefix).
     * Returns array keyed by region label, each containing matching orders.
     */
    public function groupByRegion(int $tenantId, array $saleOrderIds = []): array
    {
        $query = SaleOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['faturado', 'separacao'])
            ->whereNotNull('shipping_zipcode')
            ->select('id', 'identify', 'client_id', 'total', 'shipping_zipcode',
                     'shipping_city', 'shipping_state', 'shipping_address',
                     'estimated_delivery')
            ->with('client:id,name,company_name,trade_name,phone');

        if (!empty($saleOrderIds)) {
            $query->whereIn('id', $saleOrderIds);
        }

        $orders = $query->orderBy('shipping_zipcode')->get();

        $groups = [];

        foreach ($orders as $order) {
            $regionKey   = $this->extractRegionKey($order->shipping_zipcode);
            $regionLabel = $this->buildRegionLabel($order->shipping_city, $order->shipping_state, $regionKey);

            $groups[$regionLabel][] = $this->formatOrderStop($order, count($groups[$regionLabel] ?? []) + 1);
        }

        return array_map(function (array $stops, string $region) {
            return [
                'region'      => $region,
                'stop_count'  => count($stops),
                'total_value' => round(array_sum(array_column($stops, 'order_total')), 2),
                'stops'       => $stops,
            ];
        }, $groups, array_keys($groups));
    }

    /**
     * Optimizes the stop sequence for a shipment.
     * Algorithm: sort by delivery_window_start (respecting time constraints)
     * then by zipcode (geographic proximity proxy).
     * Persists the sequence and estimated_km on the pivot and shipment.
     */
    public function optimizeRoute(Shipment $shipment): array
    {
        $shipment->load('saleOrders');

        $stops = $shipment->saleOrders->map(function (SaleOrder $order) {
            $pivot = $order->pivot;
            return [
                'sale_order_id'        => $order->id,
                'identify'             => $order->identify,
                'client_name'          => $order->client?->company_name ?? $order->client?->name ?? 'Sem cliente',
                'shipping_zipcode'     => $pivot->delivery_zipcode ?? $order->shipping_zipcode,
                'shipping_address'     => $order->shipping_address,
                'shipping_city'        => $order->shipping_city,
                'delivery_window_start'=> $pivot->delivery_window_start,
                'delivery_window_end'  => $pivot->delivery_window_end,
                'order_total'          => (float) $order->total,
            ];
        });

        // Sort: orders with a window first (by window start), then by zipcode
        $sorted = $stops->sortBy(function (array $stop) {
            $window = $stop['delivery_window_start'] ?? null;
            $zip    = $stop['shipping_zipcode'] ?? '99999-999';
            return ($window ? '0_' . $window : '1_') . '_' . str_replace('-', '', $zip);
        })->values();

        $estimatedKm = count($sorted) > 1
            ? $this->estimateKm($sorted)
            : 0.0;

        // Persist sequence on pivot table
        DB::transaction(function () use ($shipment, $sorted, $estimatedKm) {
            foreach ($sorted as $seq => $stop) {
                DB::table('shipment_sale_order')
                    ->where('shipment_id', $shipment->id)
                    ->where('sale_order_id', $stop['sale_order_id'])
                    ->update(['delivery_sequence' => $seq + 1]);
            }

            $routeSummary = $sorted->map(fn ($s, $i) => [
                'sequence'      => $i + 1,
                'sale_order_id' => $s['sale_order_id'],
                'identify'      => $s['identify'],
                'client'        => $s['client_name'],
                'zipcode'       => $s['shipping_zipcode'],
                'window'        => $s['delivery_window_start']
                    ? "{$s['delivery_window_start']} - {$s['delivery_window_end']}"
                    : null,
            ])->values()->toArray();

            $shipment->update([
                'optimized_route' => $routeSummary,
                'estimated_km'    => $estimatedKm,
            ]);
        });

        return [
            'shipment_id'    => $shipment->id,
            'stops'          => $sorted->count(),
            'estimated_km'   => $estimatedKm,
            'optimized_route' => $shipment->fresh()->optimized_route,
        ];
    }

    /**
     * Calculates delivery cost for a shipment.
     * Returns cost breakdown: fuel, driver, total and cost per delivery.
     */
    public function calculateCost(
        Shipment $shipment,
        float $kmPerLiter     = self::DEFAULT_KM_PER_LITER,
        float $fuelPricePerL  = self::DEFAULT_FUEL_PRICE,
        float $driverCostKm   = self::DEFAULT_DRIVER_COST_KM,
    ): array {
        $km = (float) ($shipment->estimated_km ?? 0);

        if ($km <= 0) {
            $this->optimizeRoute($shipment->fresh(['saleOrders']));
            $km = (float) $shipment->fresh()->estimated_km;
        }

        $stopCount        = $shipment->saleOrders()->count();
        $fuelCost         = ($km / max($kmPerLiter, 0.1)) * $fuelPricePerL;
        $driverCost       = $km * $driverCostKm;
        $totalCost        = round($fuelCost + $driverCost, 2);
        $costPerDelivery  = $stopCount > 0 ? round($totalCost / $stopCount, 2) : 0.0;

        $shipment->update([
            'delivery_cost'    => $totalCost,
            'cost_per_delivery' => $costPerDelivery,
        ]);

        return [
            'estimated_km'       => $km,
            'stop_count'         => $stopCount,
            'fuel_cost'          => round($fuelCost, 2),
            'driver_cost'        => round($driverCost, 2),
            'total_cost'         => $totalCost,
            'cost_per_delivery'  => $costPerDelivery,
            'parameters' => [
                'km_per_liter'   => $kmPerLiter,
                'fuel_price'     => $fuelPricePerL,
                'driver_cost_km' => $driverCostKm,
            ],
        ];
    }

    /**
     * Sets delivery window for a specific order within a shipment.
     */
    public function setDeliveryWindow(
        Shipment $shipment,
        int $saleOrderId,
        string $windowStart,
        string $windowEnd,
    ): void {
        DB::table('shipment_sale_order')
            ->where('shipment_id', $shipment->id)
            ->where('sale_order_id', $saleOrderId)
            ->update([
                'delivery_window_start' => $windowStart,
                'delivery_window_end'   => $windowEnd,
            ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function extractRegionKey(string $zipcode): string
    {
        $digits = preg_replace('/\D/', '', $zipcode);
        return substr($digits, 0, self::CEP_REGION_PREFIX_LENGTH);
    }

    private function buildRegionLabel(?string $city, ?string $state, string $prefix): string
    {
        if ($city && $state) {
            return "{$city} - {$state} (CEP {$prefix}xxx)";
        }
        return "Região CEP {$prefix}xxx";
    }

    private function formatOrderStop(SaleOrder $order, int $sequence): array
    {
        return [
            'sale_order_id'    => $order->id,
            'identify'         => $order->identify,
            'client_name'      => $order->client?->company_name ?? $order->client?->trade_name ?? $order->client?->name ?? 'Sem cliente',
            'client_phone'     => $order->client?->phone,
            'shipping_address' => $order->shipping_address,
            'shipping_city'    => $order->shipping_city,
            'shipping_state'   => $order->shipping_state,
            'shipping_zipcode' => $order->shipping_zipcode,
            'order_total'      => (float) $order->total,
            'estimated_delivery' => $order->estimated_delivery,
            'suggested_sequence' => $sequence,
        ];
    }

    private function estimateKm(Collection $stops): float
    {
        // Simple proxy: each consecutive stop pair contributes AVG_KM_PER_STOP km
        // In production, replace with OSRM/Google Maps Distance Matrix API call
        $pairs = max(0, $stops->count() - 1);
        $baseKm = ($pairs * self::AVG_KM_PER_STOP) + 20.0; // +20km depot → first stop
        return round($baseKm, 2);
    }
}
