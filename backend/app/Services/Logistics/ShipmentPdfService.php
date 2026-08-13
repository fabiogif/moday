<?php

namespace App\Services\Logistics;

use App\Models\SaleOrder;
use App\Models\Shipment;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class ShipmentPdfService
{
    private const PDF_RELATIONS = [
        'saleOrders.client',
        'saleOrders.items.product',
        'vehicle',
        'driver',
        'carrier',
        'occurrences',
    ];

    public function __construct(
        private readonly ShipmentRepositoryInterface $shipmentRepository,
    ) {}

    public function streamForTenant(int $tenantId, int $shipmentId): Response
    {
        $shipment = $this->shipmentRepository->findForTenant(
            $tenantId,
            $shipmentId,
            self::PDF_RELATIONS,
        );

        if (!$shipment) {
            throw (new ModelNotFoundException())->setModel(Shipment::class, [$shipmentId]);
        }

        $html = view('pdfs.shipment.romaneio', $this->buildViewData($shipment))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->stream("romaneio-{$shipment->identify}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Shipment $shipment): array
    {
        $statusLabels = [
            'draft'      => 'Rascunho',
            'dispatched' => 'Em trânsito',
            'delivered'  => 'Entregue',
        ];

        $occurrenceTypeLabels = [
            'delay'   => 'Atraso',
            'damage'  => 'Avaria',
            'refused' => 'Recusa',
            'absent'  => 'Ausência',
            'other'   => 'Outro',
        ];

        $estimatedTime = '—';
        if ($shipment->estimated_duration_minutes) {
            $hours = intdiv((int) $shipment->estimated_duration_minutes, 60);
            $mins = (int) $shipment->estimated_duration_minutes % 60;
            $estimatedTime = $hours > 0 ? "{$hours}h {$mins}min" : "{$mins}min";
        }

        $freightUnit = '—';
        if ($shipment->freight_weight_unit !== null) {
            $unitSuffix = ($shipment->freight_weight_charge_mode === 'per_cte') ? '/CT-e' : '/kg';
            $freightUnit = 'R$ ' . number_format((float) $shipment->freight_weight_unit, 4, ',', '.') . $unitSuffix;
        }

        $routeOrderSource = ($shipment->route_order_source ?? 'system') === 'manual'
            ? 'Definida manualmente'
            : 'Definida pelo sistema';

        $stops = $this->buildOrderedStops($shipment);

        $occurrences = $shipment->occurrences->map(function ($occ) use ($occurrenceTypeLabels) {
            return [
                'type' => $occurrenceTypeLabels[$occ->type] ?? $occ->type,
                'description' => $occ->description,
                'occurred_at' => $occ->occurred_at?->format('d/m/Y H:i') ?? '—',
            ];
        })->all();

        return [
            'generatedAt' => now()->format('d/m/Y H:i'),
            'identify' => $shipment->identify,
            'status' => $statusLabels[$shipment->status] ?? $shipment->status,
            'routeName' => $shipment->route_name ?? '—',
            'driverName' => $shipment->driver_name ?? '—',
            'vehiclePlate' => $shipment->vehicle_plate ?? '—',
            'stopCount' => $shipment->saleOrders->count(),
            'routeOrderSource' => $routeOrderSource,
            'estimatedKm' => $shipment->estimated_km
                ? number_format((float) $shipment->estimated_km, 2, ',', '.') . ' km'
                : '—',
            'estimatedTime' => $estimatedTime,
            'totalWeight' => $shipment->total_weight_kg
                ? number_format((float) $shipment->total_weight_kg, 3, ',', '.') . ' kg'
                : '—',
            'totalVolume' => $shipment->total_volume_m3
                ? number_format((float) $shipment->total_volume_m3, 4, ',', '.') . ' m³'
                : '—',
            'freightWeight' => $shipment->freight_weight_amount !== null
                ? 'R$ ' . number_format((float) $shipment->freight_weight_amount, 2, ',', '.')
                : '—',
            'freightUnit' => $freightUnit,
            'stops' => $stops,
            'occurrences' => $occurrences,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildOrderedStops(Shipment $shipment): array
    {
        $optimizedRoute = is_array($shipment->optimized_route) ? $shipment->optimized_route : [];
        $ordersById = $shipment->saleOrders->keyBy('id');
        $orderedStops = [];

        if ($optimizedRoute !== []) {
            foreach ($optimizedRoute as $stop) {
                $orderId = $stop['sale_order_id'] ?? null;
                $order = $orderId ? ($ordersById[$orderId] ?? null) : null;
                $orderedStops[] = $this->mapStopRow(
                    sequence: (int) ($stop['sequence'] ?? 0),
                    order: $order,
                    identify: $stop['identify'] ?? ($order?->identify ?? '—'),
                    clientFallback: $stop['client'] ?? null,
                    eta: $stop['eta'] ?? null,
                    window: $stop['window'] ?? null,
                    windowViolation: !empty($stop['window_violation']),
                    addressFallback: $stop['address'] ?? null,
                );
            }

            return $orderedStops;
        }

        foreach ($shipment->saleOrders as $i => $order) {
            $orderedStops[] = $this->mapStopRow(
                sequence: $i + 1,
                order: $order,
                identify: $order->identify,
                clientFallback: null,
                eta: null,
                window: null,
                windowViolation: false,
                addressFallback: null,
            );
        }

        return $orderedStops;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapStopRow(
        int $sequence,
        ?SaleOrder $order,
        string $identify,
        ?string $clientFallback,
        ?string $eta,
        ?string $window,
        bool $windowViolation,
        ?string $addressFallback,
    ): array {
        $load = $this->resolveLoadDetails($order);

        return [
            'sequence' => $sequence,
            'identify' => $identify,
            'client_name' => $this->resolveClientName($order, $clientFallback),
            'client_phone' => $this->resolveClientPhone($order),
            'nfe' => $this->formatNfe($order),
            'city' => $order?->shipping_city ?? '—',
            'eta' => $eta ?? '—',
            'window' => $window ?? '—',
            'window_violation' => $windowViolation,
            'address' => $this->resolveAddress($order, $addressFallback),
            'volumes' => $load['volumes'],
            'weight' => $load['weight'],
            'products' => $load['products'],
        ];
    }

    private function resolveClientName(?SaleOrder $order, ?string $fallback = null): string
    {
        if ($fallback) {
            return $fallback;
        }

        $client = $order?->client;
        if (!$client) {
            return '—';
        }

        return $client->company_name
            ?: $client->trade_name
            ?: $client->name
            ?: '—';
    }

    private function resolveClientPhone(?SaleOrder $order): string
    {
        $client = $order?->client;
        if (!$client) {
            return '—';
        }

        return $client->phone
            ?: $client->whatsapp
            ?: $client->contact_phone
            ?: '—';
    }

    private function formatNfe(?SaleOrder $order): string
    {
        if (!$order || blank($order->nfe_number)) {
            return '—';
        }

        $nfe = (string) $order->nfe_number;
        if (!blank($order->nfe_series)) {
            $nfe .= " / Série {$order->nfe_series}";
        }

        return $nfe;
    }

    /**
     * @return array{volumes: string, weight: string, products: string}
     */
    private function resolveLoadDetails(?SaleOrder $order): array
    {
        if (!$order) {
            return [
                'volumes' => '—',
                'weight' => '—',
                'products' => '—',
            ];
        }

        $items = $order->items ?? collect();
        $totalVolumes = 0.0;
        $totalWeight = 0.0;
        $descriptions = [];

        foreach ($items as $item) {
            $qty = (float) $item->quantity;
            $product = $item->product;
            $unitsPerBox = (float) ($product?->units_per_box ?? 0);
            $volumes = $unitsPerBox > 0 ? (int) ceil($qty / $unitsPerBox) : $qty;
            $totalVolumes += $volumes;

            $name = $product?->name ?: 'Produto';
            $sku = $product?->sku ? " ({$product->sku})" : '';
            $qtyLabel = rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
            $descriptions[] = "{$qtyLabel}× {$name}{$sku}";

            if ($product && (float) $product->weight > 0) {
                $totalWeight += $qty * (float) $product->weight;
            }
        }

        return [
            'volumes' => $totalVolumes > 0
                ? rtrim(rtrim(number_format($totalVolumes, 3, ',', '.'), '0'), ',')
                : '—',
            'weight' => $totalWeight > 0
                ? number_format($totalWeight, 3, ',', '.') . ' kg'
                : '—',
            'products' => $descriptions !== [] ? implode('; ', $descriptions) : '—',
        ];
    }

    private function resolveAddress(?SaleOrder $order, ?string $fallback = null): string
    {
        if ($fallback) {
            return $fallback;
        }

        if (!$order) {
            return '—';
        }

        $parts = [];
        $shippingAddress = $order->shipping_address;

        if (is_array($shippingAddress)) {
            $street = $shippingAddress['street'] ?? $shippingAddress['logradouro'] ?? null;
            $number = $shippingAddress['number'] ?? $shippingAddress['numero'] ?? null;
            $neighborhood = $shippingAddress['neighborhood'] ?? $shippingAddress['bairro'] ?? null;
            if ($street) {
                $parts[] = trim($street . ($number ? ", {$number}" : ''));
            }
            if ($neighborhood) {
                $parts[] = $neighborhood;
            }
        } elseif (is_string($shippingAddress) && $shippingAddress !== '') {
            $parts[] = $shippingAddress;
        }

        if ($order->shipping_city) {
            $parts[] = $order->shipping_city . ($order->shipping_state ? "/{$order->shipping_state}" : '');
        }
        if ($order->shipping_zipcode) {
            $parts[] = "CEP {$order->shipping_zipcode}";
        }

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }
}
