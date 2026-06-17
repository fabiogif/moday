<?php

namespace App\Services\Logistics;

use App\Models\GeocodedAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleMapsRoutingClient
{
    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const ROUTES_URL = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public function isConfigured(): bool
    {
        return !empty($this->apiKey());
    }

    public function apiKey(): ?string
    {
        $key = config('services.google_maps.api_key');
        return $key ? (string) $key : null;
    }

    /**
     * @return array{lat: float, lng: float, formatted_address: string|null}
     */
    private const IMPRECISE_LOCATION_TYPES = ['country', 'administrative_area_level_1'];

    public function geocode(int $tenantId, string $addressQuery): array
    {
        $hash = hash('sha256', mb_strtolower(trim($addressQuery)));

        $cached = GeocodedAddress::query()
            ->where('tenant_id', $tenantId)
            ->where('address_hash', $hash)
            ->first();

        if ($cached) {
            return [
                'lat' => (float) $cached->latitude,
                'lng' => (float) $cached->longitude,
                'formatted_address' => $cached->formatted_address,
            ];
        }

        if (!$this->isConfigured()) {
            throw new RuntimeException('Google Maps API key não configurada.');
        }

        $response = Http::timeout(15)->get(self::GEOCODE_URL, [
            'address' => $addressQuery,
            'region' => 'br',
            'key' => $this->apiKey(),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Falha ao geocodificar endereço.');
        }

        $data = $response->json();
        $result = $data['results'][0] ?? null;

        if (!$result || ($data['status'] ?? '') !== 'OK') {
            throw new RuntimeException('Endereço não encontrado: ' . $addressQuery);
        }

        $resultTypes = $result['types'] ?? [];
        if (array_intersect($resultTypes, self::IMPRECISE_LOCATION_TYPES)) {
            Log::warning('Geocodificação retornou localização imprecisa (país/estado), descartando', [
                'query' => $addressQuery,
                'types' => $resultTypes,
            ]);
            throw new RuntimeException('Endereço muito genérico para geocodificar: ' . $addressQuery);
        }

        $location = $result['geometry']['location'];
        $lat = (float) $location['lat'];
        $lng = (float) $location['lng'];
        $formatted = $result['formatted_address'] ?? null;

        GeocodedAddress::create([
            'tenant_id' => $tenantId,
            'address_hash' => $hash,
            'formatted_address' => $formatted,
            'latitude' => $lat,
            'longitude' => $lng,
            'source' => 'google',
            'geocoded_at' => now(),
        ]);

        return ['lat' => $lat, 'lng' => $lng, 'formatted_address' => $formatted];
    }

    /**
     * @param  array{lat: float, lng: float}  $origin
     * @param  array<int, array{lat: float, lng: float, sale_order_id: int}>  $stops
     * @return array{
     *   ordered_stop_ids: int[],
     *   total_distance_meters: int,
     *   total_duration_seconds: int,
     *   polyline: string|null,
     *   legs: array<int, array{duration_seconds: int, distance_meters: int}>
     * }
     */
    public function computeOptimizedRoute(array $origin, array $stops): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Google Maps API key não configurada.');
        }

        if ($stops === []) {
            return [
                'ordered_stop_ids' => [],
                'total_distance_meters' => 0,
                'total_duration_seconds' => 0,
                'polyline' => null,
                'legs' => [],
            ];
        }

        $intermediates = array_map(fn (array $stop) => [
            'location' => [
                'latLng' => [
                    'latitude' => $stop['lat'],
                    'longitude' => $stop['lng'],
                ],
            ],
        ], array_values(array_filter($stops, fn (array $s) => is_numeric($s['lat'] ?? null) && is_numeric($s['lng'] ?? null))));

        if ($intermediates === []) {
            throw new RuntimeException('Nenhuma parada com coordenadas válidas para roteirização.');
        }

        $validStops = array_values(array_filter($stops, fn (array $s) => is_numeric($s['lat'] ?? null) && is_numeric($s['lng'] ?? null)));
        $lastStop = $validStops[count($validStops) - 1];

        $body = [
            'origin' => [
                'location' => [
                    'latLng' => [
                        'latitude' => $origin['lat'],
                        'longitude' => $origin['lng'],
                    ],
                ],
            ],
            'destination' => [
                'location' => [
                    'latLng' => [
                        'latitude' => $lastStop['lat'],
                        'longitude' => $lastStop['lng'],
                    ],
                ],
            ],
            'intermediates' => count($validStops) > 1 ? array_slice($intermediates, 0, -1) : [],
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE_OPTIMAL',
            'optimizeWaypointOrder' => count($validStops) > 1,
            'departureTime' => now()->addMinutes(5)->toIso8601String(),
            'computeAlternativeRoutes' => false,
            'languageCode' => 'pt-BR',
            'units' => 'METRIC',
        ];

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey(),
                'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters,routes.polyline.encodedPolyline,routes.optimizedIntermediateWaypointIndex,routes.legs.duration,routes.legs.distanceMeters',
            ])
            ->post(self::ROUTES_URL, $body);

        if (!$response->successful()) {
            Log::warning('Google Routes API error', ['body' => $response->body()]);
            throw new RuntimeException('Falha ao calcular rota otimizada.');
        }

        $route = $response->json('routes.0');

        if (!$route) {
            throw new RuntimeException('Nenhuma rota retornada pelo Google Maps.');
        }

        $optimizedIndexes = $route['optimizedIntermediateWaypointIndex'] ?? [];
        $orderedStopIds = $this->resolveStopOrder($validStops, $optimizedIndexes);

        $legs = [];
        foreach ($route['legs'] ?? [] as $leg) {
            $legs[] = [
                'duration_seconds' => $this->parseDurationSeconds($leg['duration'] ?? '0s'),
                'distance_meters' => (int) ($leg['distanceMeters'] ?? 0),
            ];
        }

        return [
            'ordered_stop_ids' => $orderedStopIds,
            'total_distance_meters' => (int) ($route['distanceMeters'] ?? 0),
            'total_duration_seconds' => $this->parseDurationSeconds($route['duration'] ?? '0s'),
            'polyline' => $route['polyline']['encodedPolyline'] ?? null,
            'legs' => $legs,
        ];
    }

    /**
     * @param  array<int, array{lat: float, lng: float, sale_order_id: int}>  $stops
     * @param  array<int, int>  $optimizedIndexes
     * @return int[]
     */
    private function resolveStopOrder(array $stops, array $optimizedIndexes): array
    {
        if (count($stops) <= 1) {
            return array_column($stops, 'sale_order_id');
        }

        $intermediateStops = array_slice($stops, 0, -1);
        $lastStop = $stops[count($stops) - 1];

        if ($optimizedIndexes === []) {
            $ordered = array_column($stops, 'sale_order_id');
            return $ordered;
        }

        $orderedIds = [];
        foreach ($optimizedIndexes as $index) {
            if (isset($intermediateStops[$index])) {
                $orderedIds[] = $intermediateStops[$index]['sale_order_id'];
            }
        }

        $orderedIds[] = $lastStop['sale_order_id'];

        $missing = array_diff(array_column($stops, 'sale_order_id'), $orderedIds);
        foreach ($missing as $id) {
            $orderedIds[] = $id;
        }

        return array_values($orderedIds);
    }

    private function parseDurationSeconds(mixed $duration): int
    {
        if (is_array($duration)) {
            return (int) ($duration['seconds'] ?? 0);
        }

        if (is_numeric($duration)) {
            return (int) $duration;
        }

        if (is_string($duration) && preg_match('/(\d+)s/', $duration, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
