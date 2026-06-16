<?php

namespace App\Services\Logistics;

use App\Models\SaleOrder;
use App\Models\Shipment;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryRouteService
{
    private const CEP_REGION_PREFIX_LENGTH = 3;
    private const AVG_KM_PER_STOP = 3.0;
    private const ROAD_FACTOR = 1.35;
    private const AVG_SPEED_KMH = 35.0;
    private const DEFAULT_KM_PER_LITER = 10.0;
    private const DEFAULT_FUEL_PRICE = 6.50;
    private const DEFAULT_DRIVER_COST_KM = 0.50;

    public function __construct(
        private readonly GoogleMapsRoutingClient $googleMaps,
    ) {}

    public function groupByRegion(int $tenantId, array $saleOrderIds = []): array
    {
        $query = SaleOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['faturado', 'separacao'])
            ->whereNotNull('shipping_zipcode')
            ->select('id', 'identify', 'client_id', 'total', 'shipping_zipcode',
                'shipping_city', 'shipping_state', 'shipping_address',
                'estimated_delivery', 'delivery_window_start', 'delivery_window_end')
            ->with('client:id,name,company_name,trade_name,phone');

        if (!empty($saleOrderIds)) {
            $query->whereIn('id', $saleOrderIds);
        }

        $orders = $query->orderBy('shipping_zipcode')->get();
        $groups = [];

        foreach ($orders as $order) {
            $regionKey = $this->extractRegionKey($order->shipping_zipcode);
            $regionLabel = $this->buildRegionLabel($order->shipping_city, $order->shipping_state, $regionKey);
            $groups[$regionLabel][] = $this->formatOrderStop($order, count($groups[$regionLabel] ?? []) + 1);
        }

        return array_map(function (array $stops, string $region) {
            return [
                'region' => $region,
                'stop_count' => count($stops),
                'total_value' => round(array_sum(array_column($stops, 'order_total')), 2),
                'stops' => $stops,
            ];
        }, $groups, array_keys($groups));
    }

    public function optimizeRoute(Shipment $shipment): array
    {
        $shipment->load(['saleOrders.client', 'tenant']);

        if ($this->googleMaps->isConfigured()) {
            try {
                return $this->optimizeWithGoogle($shipment);
            } catch (\Throwable $ex) {
                Log::warning('Google route optimization failed, using fallback', [
                    'shipment_id' => $shipment->id,
                    'error' => $ex->getMessage(),
                ]);
            }
        }

        return $this->optimizeWithHeuristic($shipment);
    }

    public function calculateCost(
        Shipment $shipment,
        float $kmPerLiter = self::DEFAULT_KM_PER_LITER,
        float $fuelPricePerL = self::DEFAULT_FUEL_PRICE,
        float $driverCostKm = self::DEFAULT_DRIVER_COST_KM,
    ): array {
        // Use vehicle-specific fuel efficiency if available
        if ($shipment->vehicle_id && $shipment->vehicle?->km_per_liter > 0) {
            $kmPerLiter = (float) $shipment->vehicle->km_per_liter;
        }

        $km = (float) ($shipment->estimated_km ?? 0);

        if ($km <= 0) {
            $this->optimizeRoute($shipment->fresh(['saleOrders', 'tenant']));
            $km = (float) $shipment->fresh()->estimated_km;
        }

        $stopCount = $shipment->saleOrders()->count();
        $fuelCost = ($km / max($kmPerLiter, 0.1)) * $fuelPricePerL;
        $driverCost = $km * $driverCostKm;
        $totalCost = round($fuelCost + $driverCost, 2);
        $costPerDelivery = $stopCount > 0 ? round($totalCost / $stopCount, 2) : 0.0;

        $shipment->update([
            'delivery_cost' => $totalCost,
            'cost_per_delivery' => $costPerDelivery,
        ]);

        return [
            'estimated_km' => $km,
            'estimated_duration_minutes' => $shipment->estimated_duration_minutes,
            'stop_count' => $stopCount,
            'fuel_cost' => round($fuelCost, 2),
            'driver_cost' => round($driverCost, 2),
            'total_cost' => $totalCost,
            'cost_per_delivery' => $costPerDelivery,
            'parameters' => [
                'km_per_liter' => $kmPerLiter,
                'fuel_price' => $fuelPricePerL,
                'driver_cost_km' => $driverCostKm,
            ],
        ];
    }

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
                'delivery_window_end' => $windowEnd,
            ]);
    }

    private function optimizeWithGoogle(Shipment $shipment): array
    {
        $tenantId = (int) $shipment->tenant_id;
        $depot = $this->resolveDepotCoordinatesSafe($shipment->tenant);

        $stopData = [];
        foreach ($shipment->saleOrders as $order) {
            $coords = $this->resolveOrderCoordinatesSafe($tenantId, $order);
            $stopData[] = [
                'sale_order_id' => $order->id,
                'identify' => $order->identify,
                'client_name' => $order->client?->company_name ?? $order->client?->name ?? 'Sem cliente',
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'shipping_zipcode' => $order->pivot->delivery_zipcode ?? $order->shipping_zipcode,
                'shipping_city' => $order->shipping_city,
                'shipping_state' => $order->shipping_state,
                'shipping_address' => $this->formatAddressString($order->shipping_address),
                'delivery_window_start' => $order->pivot->delivery_window_start ?? $order->delivery_window_start,
                'delivery_window_end' => $order->pivot->delivery_window_end ?? $order->delivery_window_end,
                'order_total' => (float) $order->total,
            ];
        }

        if ($stopData === []) {
            return $this->emptyRouteResult($shipment);
        }

        $googleStops = array_values(array_filter(array_map(fn (array $s) => [
            'lat' => $s['lat'],
            'lng' => $s['lng'],
            'sale_order_id' => $s['sale_order_id'],
        ], $stopData), fn (array $s) => $s['lat'] !== null && $s['lng'] !== null));

        $fallbackMetrics = $this->estimateMetricsFromPath($depot, $stopData);

        try {
            $route = $this->googleMaps->computeOptimizedRoute($depot, $googleStops);
        } catch (\Throwable $ex) {
            Log::warning('Google Routes API unavailable, using coordinate estimate', [
                'shipment_id' => $shipment->id,
                'error' => $ex->getMessage(),
            ]);
            $route = [
                'ordered_stop_ids' => array_column($stopData, 'sale_order_id'),
                'total_distance_meters' => (int) round($fallbackMetrics['km'] * 1000),
                'total_duration_seconds' => $fallbackMetrics['duration_minutes'] * 60,
                'polyline' => null,
                'legs' => $fallbackMetrics['legs'],
            ];
        }

        if (($route['total_distance_meters'] ?? 0) <= 0 && $fallbackMetrics['km'] > 0) {
            $route['total_distance_meters'] = (int) round($fallbackMetrics['km'] * 1000);
            $route['total_duration_seconds'] = $fallbackMetrics['duration_minutes'] * 60;
            $route['legs'] = $fallbackMetrics['legs'];
        }

        $orderedStops = $this->reorderStopsByIds($stopData, $route['ordered_stop_ids']);
        $orderedStops = $this->applyWindowAwareOrdering($orderedStops, $route['legs']);

        $legs = $route['legs'] !== [] ? $route['legs'] : $fallbackMetrics['legs'];
        $routeSummary = $this->buildRouteSummary($orderedStops, $legs);
        $estimatedKm = round(($route['total_distance_meters'] ?? 0) / 1000, 2);
        if ($estimatedKm <= 0) {
            $estimatedKm = $fallbackMetrics['km'];
        }
        $durationMinutes = (int) ceil(($route['total_duration_seconds'] ?? 0) / 60);
        if ($durationMinutes <= 0) {
            $durationMinutes = $fallbackMetrics['duration_minutes'];
        }
        $region = $this->dominantRegionLabel($orderedStops);

        $this->persistRoute($shipment, $orderedStops, $routeSummary, $estimatedKm, $durationMinutes, $route['polyline'] ?? null, $region);

        return [
            'shipment_id' => $shipment->id,
            'stops' => count($routeSummary),
            'estimated_km' => $estimatedKm,
            'estimated_duration_minutes' => $durationMinutes,
            'optimized_route' => $shipment->fresh()->optimized_route,
            'route_polyline' => $route['polyline'] ?? null,
            'window_violations' => count(array_filter($routeSummary, fn ($s) => $s['window_violation'] ?? false)),
            'provider' => ($route['polyline'] ?? null) ? 'google' : 'estimated',
        ];
    }

    private function optimizeWithHeuristic(Shipment $shipment): array
    {
        $tenantId = (int) $shipment->tenant_id;
        $depot = $this->resolveDepotCoordinatesSafe($shipment->tenant);

        $stops = $shipment->saleOrders->map(function (SaleOrder $order) use ($tenantId) {
            $pivot = $order->pivot;
            $coords = $this->resolveOrderCoordinatesSafe($tenantId, $order);

            return [
                'sale_order_id' => $order->id,
                'identify' => $order->identify,
                'client_name' => $order->client?->company_name ?? $order->client?->name ?? 'Sem cliente',
                'shipping_zipcode' => $pivot->delivery_zipcode ?? $order->shipping_zipcode,
                'shipping_city' => $order->shipping_city,
                'shipping_state' => $order->shipping_state,
                'shipping_address' => $this->formatAddressString($order->shipping_address),
                'delivery_window_start' => $pivot->delivery_window_start ?? $order->delivery_window_start,
                'delivery_window_end' => $pivot->delivery_window_end ?? $order->delivery_window_end,
                'order_total' => (float) $order->total,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ];
        });

        $sorted = $stops->sortBy(function (array $stop) {
            $window = $stop['delivery_window_start'] ?? null;
            $zip = $stop['shipping_zipcode'] ?? '99999-999';
            return ($window ? '0_' . $window : '1_') . '_' . str_replace('-', '', $zip);
        })->values()->all();

        $metrics = $this->estimateMetricsFromPath($depot, $sorted);
        $routeSummary = $this->buildRouteSummary($sorted, $metrics['legs']);
        $region = $this->dominantRegionLabel($sorted);

        $this->persistRoute(
            $shipment,
            $sorted,
            $routeSummary,
            $metrics['km'],
            $metrics['duration_minutes'],
            null,
            $region,
        );

        return [
            'shipment_id' => $shipment->id,
            'stops' => count($routeSummary),
            'estimated_km' => $metrics['km'],
            'estimated_duration_minutes' => $metrics['duration_minutes'],
            'optimized_route' => $shipment->fresh()->optimized_route,
            'route_polyline' => null,
            'window_violations' => 0,
            'provider' => 'estimated',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $stops
     * @param  array<int, array{duration_seconds: int, distance_meters: int}>  $legs
     * @return array<int, array<string, mixed>>
     */
    private function buildRouteSummary(array $stops, array $legs): array
    {
        $serviceMinutes = (int) config('services.google_maps.service_time_minutes', 10);
        $currentTime = now()->addMinutes(5);
        $summary = [];

        foreach ($stops as $index => $stop) {
            if ($index > 0) {
                $currentTime = $currentTime->copy()->addMinutes($serviceMinutes);
            }

            $legDuration = $legs[$index]['duration_seconds'] ?? 0;
            $currentTime = $currentTime->copy()->addSeconds($legDuration);

            $windowStart = $stop['delivery_window_start'] ?? null;
            $windowEnd = $stop['delivery_window_end'] ?? null;
            $windowViolation = false;

            if ($windowEnd) {
                $endTime = $this->timeToday($windowEnd);
                $windowViolation = $currentTime->greaterThan($endTime);
            }

            $addressParts = array_filter([
                $stop['shipping_address'] ?? null,
                $stop['shipping_city'] ?? null,
                $stop['shipping_state'] ?? null,
            ]);
            $address = $addressParts
                ? implode(', ', $addressParts) . ($stop['shipping_zipcode'] ? ' — CEP ' . $stop['shipping_zipcode'] : '')
                : ($stop['shipping_zipcode'] ? 'CEP ' . $stop['shipping_zipcode'] : null);

            $summary[] = [
                'sequence' => $index + 1,
                'sale_order_id' => $stop['sale_order_id'],
                'identify' => $stop['identify'],
                'client' => $stop['client_name'],
                'address' => $address,
                'zipcode' => $stop['shipping_zipcode'] ?? null,
                'lat' => $this->normalizeCoordinate($stop['lat'] ?? null),
                'lng' => $this->normalizeCoordinate($stop['lng'] ?? null),
                'eta' => $currentTime->format('H:i'),
                'window' => $windowStart && $windowEnd ? "{$windowStart} - {$windowEnd}" : null,
                'window_violation' => $windowViolation,
                'duration_from_prev_sec' => $legDuration,
            ];
        }

        return $summary;
    }

    private function persistRoute(
        Shipment $shipment,
        array $stops,
        array $routeSummary,
        float $estimatedKm,
        ?int $durationMinutes,
        ?string $polyline,
        ?string $region,
    ): void {
        DB::transaction(function () use ($shipment, $stops, $routeSummary, $estimatedKm, $durationMinutes, $polyline, $region) {
            foreach ($routeSummary as $item) {
                DB::table('shipment_sale_order')
                    ->where('shipment_id', $shipment->id)
                    ->where('sale_order_id', $item['sale_order_id'])
                    ->update(['delivery_sequence' => $item['sequence']]);
            }

            $shipment->update([
                'optimized_route' => $routeSummary,
                'estimated_km' => $estimatedKm,
                'estimated_duration_minutes' => $durationMinutes,
                'route_polyline' => $polyline,
                'region' => $region,
            ]);
        });
    }

    private function emptyRouteResult(Shipment $shipment): array
    {
        $shipment->update([
            'optimized_route' => [],
            'estimated_km' => 0,
            'estimated_duration_minutes' => 0,
            'route_polyline' => null,
        ]);

        return [
            'shipment_id' => $shipment->id,
            'stops' => 0,
            'estimated_km' => 0,
            'estimated_duration_minutes' => 0,
            'optimized_route' => [],
            'route_polyline' => null,
            'window_violations' => 0,
            'provider' => 'google',
        ];
    }

    /** @return array{lat: float, lng: float} */
    private function resolveDepotCoordinatesSafe(?Tenant $tenant): array
    {
        try {
            return $this->resolveDepotCoordinates($tenant);
        } catch (\Throwable $ex) {
            Log::warning('Depot geocode failed, using default center', ['error' => $ex->getMessage()]);
            return ['lat' => -23.5505, 'lng' => -46.6333];
        }
    }

    /** @return array{lat: ?float, lng: ?float} */
    private function resolveOrderCoordinatesSafe(int $tenantId, SaleOrder $order): array
    {
        try {
            $coords = $this->resolveOrderCoordinates($tenantId, $order);
            return [
                'lat' => $this->normalizeCoordinate($coords['lat']),
                'lng' => $this->normalizeCoordinate($coords['lng']),
            ];
        } catch (\Throwable $ex) {
            Log::warning('Order geocode failed', [
                'sale_order_id' => $order->id,
                'error' => $ex->getMessage(),
            ]);
            return [
                'lat' => $this->normalizeCoordinate($order->shipping_latitude),
                'lng' => $this->normalizeCoordinate($order->shipping_longitude),
            ];
        }
    }

    /**
     * Estima km, tempo e pernas da rota a partir de coordenadas (Haversine + fator de via).
     *
     * @param  array{lat: float, lng: float}  $depot
     * @param  array<int, array<string, mixed>>  $stops
     * @return array{km: float, duration_minutes: int, legs: array<int, array{duration_seconds: int, distance_meters: int}>}
     */
    private function estimateMetricsFromPath(array $depot, array $stops): array
    {
        $serviceMinutes = (int) config('services.google_maps.service_time_minutes', 10);
        $coordStops = [];

        foreach ($stops as $stop) {
            $lat = $this->normalizeCoordinate($stop['lat'] ?? null);
            $lng = $this->normalizeCoordinate($stop['lng'] ?? null);
            if ($lat !== null && $lng !== null) {
                $coordStops[] = ['lat' => $lat, 'lng' => $lng];
            }
        }

        if ($coordStops === []) {
            $stopCount = count($stops);
            $pairs = max(0, $stopCount - 1);
            $km = $stopCount > 0 ? round(($pairs * self::AVG_KM_PER_STOP) + 8.0, 2) : 0.0;
            $travelMinutes = (int) ceil(($km / self::AVG_SPEED_KMH) * 60);
            $durationMinutes = max($travelMinutes + ($stopCount * $serviceMinutes), $serviceMinutes);

            return ['km' => $km, 'duration_minutes' => $durationMinutes, 'legs' => []];
        }

        $path = array_merge([$depot], $coordStops);
        $totalKm = 0.0;
        $legs = [];

        for ($i = 1; $i < count($path); $i++) {
            $km = $this->haversineKm(
                $path[$i - 1]['lat'],
                $path[$i - 1]['lng'],
                $path[$i]['lat'],
                $path[$i]['lng'],
            ) * self::ROAD_FACTOR;

            $totalKm += $km;
            $legs[] = [
                'distance_meters' => (int) round($km * 1000),
                'duration_seconds' => (int) ceil(($km / self::AVG_SPEED_KMH) * 3600),
            ];
        }

        $travelMinutes = (int) ceil(($totalKm / self::AVG_SPEED_KMH) * 60);
        $durationMinutes = max($travelMinutes + (count($stops) * $serviceMinutes), 1);

        return [
            'km' => round($totalKm, 2),
            'duration_minutes' => $durationMinutes,
            'legs' => $legs,
        ];
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @return array{lat: float, lng: float} */
    private function resolveDepotCoordinates(?Tenant $tenant): array
    {
        if (!$tenant) {
            return ['lat' => -23.5505, 'lng' => -46.6333];
        }

        $query = trim(implode(', ', array_filter([
            $tenant->address,
            $tenant->city,
            $tenant->state,
            $tenant->zipcode,
            'Brasil',
        ])));

        if ($query === '' || $query === 'Brasil') {
            return ['lat' => -23.5505, 'lng' => -46.6333];
        }

        return $this->googleMaps->geocode((int) $tenant->id, $query);
    }

    /** @return array{lat: float, lng: float} */
    private function resolveOrderCoordinates(int $tenantId, SaleOrder $order): array
    {
        if ($order->shipping_latitude && $order->shipping_longitude) {
            return [
                'lat' => (float) $order->shipping_latitude,
                'lng' => (float) $order->shipping_longitude,
            ];
        }

        $address = $order->shipping_address;
        if (is_array($address)) {
            $parts = array_filter([
                $address['street'] ?? $address['logradouro'] ?? null,
                $address['number'] ?? $address['numero'] ?? null,
                $address['neighborhood'] ?? $address['bairro'] ?? null,
                $order->shipping_city,
                $order->shipping_state,
                $order->shipping_zipcode,
                'Brasil',
            ]);
            $query = implode(', ', $parts);
        } else {
            $query = trim(implode(', ', array_filter([
                is_string($address) ? $address : null,
                $order->shipping_city,
                $order->shipping_state,
                $order->shipping_zipcode,
                'Brasil',
            ])));
        }

        if ($query === '' || $query === 'Brasil') {
            $query = trim(($order->shipping_zipcode ?? '') . ', Brasil');
        }

        $coords = $this->googleMaps->geocode($tenantId, $query);

        $order->update([
            'shipping_latitude' => $coords['lat'],
            'shipping_longitude' => $coords['lng'],
        ]);

        return $coords;
    }

    /**
     * @param  array<int, array<string, mixed>>  $stops
     * @param  int[]  $orderedIds
     * @return array<int, array<string, mixed>>
     */
    private function reorderStopsByIds(array $stops, array $orderedIds): array
    {
        $byId = collect($stops)->keyBy('sale_order_id');
        $ordered = [];

        foreach ($orderedIds as $id) {
            if ($byId->has($id)) {
                $ordered[] = $byId->get($id);
            }
        }

        return $ordered;
    }

    /**
     * @param  array<int, array<string, mixed>>  $stops
     * @return array<int, array<string, mixed>>
     */
    private function applyWindowAwareOrdering(array $stops, array $legs): array
    {
        $withWindow = array_values(array_filter($stops, fn ($s) => !empty($s['delivery_window_start'])));
        $withoutWindow = array_values(array_filter($stops, fn ($s) => empty($s['delivery_window_start'])));

        if ($withWindow === []) {
            return $stops;
        }

        usort($withWindow, fn ($a, $b) => strcmp((string) $a['delivery_window_start'], (string) $b['delivery_window_start']));

        return array_merge($withWindow, $withoutWindow);
    }

    /** @param  array<int, array<string, mixed>>  $stops */
    private function dominantRegionLabel(array $stops): ?string
    {
        if ($stops === []) {
            return null;
        }

        $first = $stops[0];
        $zip = $first['shipping_zipcode'] ?? null;
        if (!$zip) {
            return null;
        }

        return $this->buildRegionLabel(null, null, $this->extractRegionKey($zip));
    }

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
            'sale_order_id' => $order->id,
            'identify' => $order->identify,
            'client_name' => $order->client?->company_name ?? $order->client?->trade_name ?? $order->client?->name ?? 'Sem cliente',
            'client_phone' => $order->client?->phone,
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_state' => $order->shipping_state,
            'shipping_zipcode' => $order->shipping_zipcode,
            'order_total' => (float) $order->total,
            'estimated_delivery' => $order->estimated_delivery,
            'delivery_window_start' => $order->delivery_window_start,
            'delivery_window_end' => $order->delivery_window_end,
            'suggested_sequence' => $sequence,
        ];
    }

    private function timeToday(string $time): Carbon
    {
        return Carbon::today()->setTimeFromTimeString(substr($time, 0, 5));
    }

    private function formatAddressString(mixed $address): ?string
    {
        if (is_array($address)) {
            $parts = array_filter([
                $address['street'] ?? $address['logradouro'] ?? null,
                $address['number'] ?? $address['numero'] ?? null,
                $address['neighborhood'] ?? $address['bairro'] ?? null,
            ]);
            return $parts ? implode(', ', $parts) : null;
        }

        return is_string($address) && $address !== '' ? $address : null;
    }

    private function normalizeCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
