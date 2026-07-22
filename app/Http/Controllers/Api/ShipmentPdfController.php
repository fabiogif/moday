<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaleOrder;
use App\Models\Shipment;
use App\Services\AuthTenantService;
use Barryvdh\DomPDF\Facade\Pdf;

class ShipmentPdfController extends Controller
{
    public function __construct(private readonly AuthTenantService $authTenantService) {}

    public function show(int $id): \Symfony\Component\HttpFoundation\Response
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)
                ->with([
                    'saleOrders.client',
                    'saleOrders.items.product',
                    'vehicle',
                    'driver',
                    'carrier',
                    'occurrences',
                ])
                ->find($id);

            if (!$shipment) {
                return response()->json(['success' => false, 'message' => 'Romaneio não encontrado'], 404);
            }

            $html = $this->buildHtml($shipment);

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

            return $pdf->stream("romaneio-{$shipment->identify}.pdf");
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Erro ao gerar PDF: ' . $ex->getMessage()], 500);
        }
    }

    private function buildHtml(Shipment $shipment): string
    {
        $statusLabels = [
            'draft'      => 'Rascunho',
            'dispatched' => 'Em trânsito',
            'delivered'  => 'Entregue',
        ];
        $status = $statusLabels[$shipment->status] ?? $shipment->status;

        $occurrenceTypeLabels = [
            'delay'   => 'Atraso',
            'damage'  => 'Avaria',
            'refused' => 'Recusa',
            'absent'  => 'Ausência',
            'other'   => 'Outro',
        ];

        $now = now()->format('d/m/Y H:i');
        $identify = e($shipment->identify);
        $routeName = e($shipment->route_name ?? '—');
        $driverName = e($shipment->driver_name ?? '—');
        $vehiclePlate = e($shipment->vehicle_plate ?? '—');
        $estimatedKm = $shipment->estimated_km ? number_format((float) $shipment->estimated_km, 2, ',', '.') . ' km' : '—';
        $estimatedTime = '—';
        if ($shipment->estimated_duration_minutes) {
            $hours = intdiv($shipment->estimated_duration_minutes, 60);
            $mins = $shipment->estimated_duration_minutes % 60;
            $estimatedTime = $hours > 0 ? "{$hours}h {$mins}min" : "{$mins}min";
        }
        $totalWeight = $shipment->total_weight_kg ? number_format((float) $shipment->total_weight_kg, 3, ',', '.') . ' kg' : '—';
        $totalVolume = $shipment->total_volume_m3 ? number_format((float) $shipment->total_volume_m3, 4, ',', '.') . ' m³' : '—';
        $freightWeight = $shipment->freight_weight_amount !== null
            ? 'R$ ' . number_format((float) $shipment->freight_weight_amount, 2, ',', '.')
            : '—';
        $freightUnit = '—';
        if ($shipment->freight_weight_unit !== null) {
            $unitSuffix = ($shipment->freight_weight_charge_mode === 'per_cte') ? '/CT-e' : '/kg';
            $freightUnit = 'R$ ' . number_format((float) $shipment->freight_weight_unit, 4, ',', '.') . $unitSuffix;
        }
        $stopCount = $shipment->saleOrders->count();

        $optimizedRoute = is_array($shipment->optimized_route) ? $shipment->optimized_route : [];
        $ordersById = $shipment->saleOrders->keyBy('id');

        $orderedStops = [];
        if (!empty($optimizedRoute)) {
            foreach ($optimizedRoute as $stop) {
                $orderId = $stop['sale_order_id'] ?? null;
                $order = $orderId ? ($ordersById[$orderId] ?? null) : null;
                $orderedStops[] = [
                    'sequence' => (int) ($stop['sequence'] ?? 0),
                    'order' => $order,
                    'identify' => $stop['identify'] ?? ($order?->identify ?? '—'),
                    'client' => $stop['client'] ?? null,
                    'eta' => $stop['eta'] ?? null,
                    'window' => $stop['window'] ?? null,
                    'window_violation' => !empty($stop['window_violation']),
                    'address' => $stop['address'] ?? null,
                ];
            }
        } else {
            foreach ($shipment->saleOrders as $i => $order) {
                $orderedStops[] = [
                    'sequence' => $i + 1,
                    'order' => $order,
                    'identify' => $order->identify,
                    'client' => null,
                    'eta' => null,
                    'window' => null,
                    'window_violation' => false,
                    'address' => null,
                ];
            }
        }

        $logisticsHtml = '';
        $detailsHtml = '';

        foreach ($orderedStops as $stop) {
            /** @var SaleOrder|null $order */
            $order = $stop['order'];
            $seq = (int) $stop['sequence'];
            $orderIdentify = e($stop['identify'] ?? '—');
            $clientName = e($this->resolveClientName($order, $stop['client'] ?? null));
            $clientPhone = e($this->resolveClientPhone($order));
            $nfe = e($this->formatNfe($order));
            $city = e($order?->shipping_city ?? '—');
            $eta = e($stop['eta'] ?? '—');
            $window = e($stop['window'] ?? '—');
            $violation = $stop['window_violation'] ? '<span style="color:#dc2626"> ⚠</span>' : '';
            $load = $this->resolveLoadDetails($order);
            $volumes = e($load['volumes']);
            $weight = e($load['weight']);
            $products = e($load['products']);
            $address = e($this->resolveAddress($order, $stop['address'] ?? null));

            $logisticsHtml .= "<tr>
                <td style='text-align:center;font-weight:bold'>{$seq}</td>
                <td>{$orderIdentify}</td>
                <td>{$clientName}</td>
                <td>{$city}</td>
                <td>{$window}{$violation}</td>
                <td>{$eta}</td>
            </tr>";

            $detailsHtml .= "
            <div class='stop-card'>
              <div class='stop-title'>Entrega #{$seq} · {$orderIdentify}</div>
              <table cellspacing='0' cellpadding='0' style='width:100%;border-collapse:collapse;font-size:11px'>
                <tr>
                  <td style='width:50%;vertical-align:top;padding:4px 8px 4px 0'>
                    <div class='block-label'>Dados do cliente</div>
                    <div><strong>Nome / Razão social:</strong> {$clientName}</div>
                    <div><strong>Telefone:</strong> {$clientPhone}</div>
                    <div style='margin-top:4px'><strong>Endereço:</strong> {$address}</div>
                  </td>
                  <td style='width:50%;vertical-align:top;padding:4px 0 4px 8px'>
                    <div class='block-label'>Identificação do pedido</div>
                    <div><strong>Pedido:</strong> {$orderIdentify}</div>
                    <div><strong>Nota Fiscal:</strong> {$nfe}</div>
                  </td>
                </tr>
                <tr>
                  <td colspan='2' style='padding:8px 0 0;vertical-align:top'>
                    <div class='block-label'>Detalhes da carga</div>
                    <div><strong>Volumes:</strong> {$volumes} &nbsp;·&nbsp; <strong>Peso:</strong> {$weight}</div>
                    <div style='margin-top:2px'><strong>Produtos:</strong> {$products}</div>
                  </td>
                </tr>
              </table>
            </div>";
        }

        if ($logisticsHtml === '') {
            $logisticsHtml = "<tr><td colspan='6' style='text-align:center;color:#6b7280'>Nenhuma parada na rota</td></tr>";
            $detailsHtml = "<p style='color:#6b7280;font-size:11px'>Nenhuma entrega vinculada a este romaneio.</p>";
        }

        $occurrencesHtml = '';
        if ($shipment->occurrences->isNotEmpty()) {
            $occurrencesHtml .= '<h3 style="margin-top:24px;margin-bottom:8px;color:#374151;font-size:13px">Ocorrências</h3>
                <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:11px">
                <thead><tr style="background:#fef3c7">
                    <th style="border:1px solid #d1d5db;text-align:left">Tipo</th>
                    <th style="border:1px solid #d1d5db;text-align:left">Descrição</th>
                    <th style="border:1px solid #d1d5db;text-align:left">Data/Hora</th>
                </tr></thead><tbody>';
            foreach ($shipment->occurrences as $occ) {
                $occType = e($occurrenceTypeLabels[$occ->type] ?? $occ->type);
                $occDesc = e($occ->description);
                $occAt = $occ->occurred_at?->format('d/m/Y H:i') ?? '—';
                $occurrencesHtml .= "<tr>
                    <td style='border:1px solid #d1d5db'>{$occType}</td>
                    <td style='border:1px solid #d1d5db'>{$occDesc}</td>
                    <td style='border:1px solid #d1d5db'>{$occAt}</td>
                </tr>";
            }
            $occurrencesHtml .= '</tbody></table>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; margin: 0; padding: 0; }
  .header { background: #16a34a; color: #fff; padding: 14px 20px; display: table; width: 100%; box-sizing: border-box; }
  .header-left { display: table-cell; vertical-align: middle; }
  .header-right { display: table-cell; vertical-align: middle; text-align: right; font-size: 11px; }
  h1 { margin: 0; font-size: 18px; }
  .meta { padding: 12px 20px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
  .meta-grid { display: table; width: 100%; }
  .meta-cell { display: table-cell; width: 25%; vertical-align: top; }
  .meta-label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
  .meta-value { font-weight: bold; }
  .section { padding: 0 20px 16px; }
  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  th { background: #16a34a; color: #fff; padding: 6px 8px; text-align: left; }
  td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
  tr:nth-child(even) td { background: #f9fafb; }
  .stop-card { border: 1px solid #d1d5db; border-radius: 4px; padding: 10px 12px; margin-bottom: 10px; page-break-inside: avoid; }
  .stop-title { font-size: 12px; font-weight: bold; color: #15803d; margin-bottom: 8px; border-bottom: 1px solid #d1fae5; padding-bottom: 4px; }
  .block-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; margin-bottom: 4px; font-weight: bold; }
  .footer { margin-top: 24px; border-top: 1px solid #d1d5db; padding-top: 12px; display: table; width: 100%; }
  .sig-box { display: table-cell; width: 48%; border-top: 1px solid #374151; padding-top: 4px; font-size: 10px; color: #6b7280; text-align: center; }
  .generated { font-size: 9px; color: #9ca3af; text-align: right; margin-top: 8px; }
</style>
</head>
<body>
<div class="header">
  <div class="header-left">
    <h1>Romaneio de Entrega</h1>
    <div style="font-size:12px;margin-top:2px">{$identify} · {$status}</div>
  </div>
  <div class="header-right">
    {$now}
  </div>
</div>

<div class="meta">
  <div class="meta-grid">
    <div class="meta-cell">
      <div class="meta-label">Rota</div>
      <div class="meta-value">{$routeName}</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Motorista</div>
      <div class="meta-value">{$driverName}</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Veículo / Placa</div>
      <div class="meta-value">{$vehiclePlate}</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Paradas</div>
      <div class="meta-value">{$stopCount}</div>
    </div>
  </div>
</div>

<div class="section" style="padding-top:12px">
  <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:separate;border-spacing:4px">
    <tr>
      <td style="background:#f0fdf4;border:1px solid #d1fae5;border-radius:4px;padding:8px;text-align:center">
        <div style="font-size:9px;color:#6b7280">Distância est.</div>
        <div style="font-size:13px;font-weight:bold;color:#15803d">{$estimatedKm}</div>
      </td>
      <td style="background:#f0fdf4;border:1px solid #d1fae5;border-radius:4px;padding:8px;text-align:center">
        <div style="font-size:9px;color:#6b7280">Tempo est.</div>
        <div style="font-size:13px;font-weight:bold;color:#15803d">{$estimatedTime}</div>
      </td>
      <td style="background:#f0fdf4;border:1px solid #d1fae5;border-radius:4px;padding:8px;text-align:center">
        <div style="font-size:9px;color:#6b7280">Peso total</div>
        <div style="font-size:13px;font-weight:bold;color:#15803d">{$totalWeight}</div>
      </td>
      <td style="background:#f0fdf4;border:1px solid #d1fae5;border-radius:4px;padding:8px;text-align:center">
        <div style="font-size:9px;color:#6b7280">Volume total</div>
        <div style="font-size:13px;font-weight:bold;color:#15803d">{$totalVolume}</div>
      </td>
      <td style="background:#f0fdf4;border:1px solid #d1fae5;border-radius:4px;padding:8px;text-align:center">
        <div style="font-size:9px;color:#6b7280">Frete Peso</div>
        <div style="font-size:13px;font-weight:bold;color:#15803d">{$freightWeight}</div>
      </td>
      <td style="background:#f0fdf4;border:1px solid #d1fae5;border-radius:4px;padding:8px;text-align:center">
        <div style="font-size:9px;color:#6b7280">Tarifa FP</div>
        <div style="font-size:13px;font-weight:bold;color:#15803d">{$freightUnit}</div>
      </td>
    </tr>
  </table>
</div>

<div class="section">
  <h3 style="margin-bottom:8px;color:#374151;font-size:13px">Logística — ordem das entregas na rota</h3>
  <table cellspacing="0" cellpadding="0">
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th>Pedido</th>
        <th>Cliente</th>
        <th>Cidade</th>
        <th>Janela</th>
        <th>ETA</th>
      </tr>
    </thead>
    <tbody>
      {$logisticsHtml}
    </tbody>
  </table>
</div>

<div class="section">
  <h3 style="margin-bottom:8px;color:#374151;font-size:13px">Detalhes das entregas</h3>
  {$detailsHtml}

  {$occurrencesHtml}

  <div class="footer">
    <div style="display:table;width:80%;margin:0 auto">
      <div class="sig-box" style="padding-right:20px">
        Assinatura do Motorista
      </div>
      <div style="display:table-cell;width:4%"></div>
      <div class="sig-box" style="padding-left:20px">
        Assinatura do Recebedor
      </div>
    </div>
  </div>
  <div class="generated">Gerado em {$now} · DistribTec</div>
</div>
</body>
</html>
HTML;
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

        $phone = $client->phone
            ?: $client->whatsapp
            ?: $client->contact_phone;

        return $phone ?: '—';
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

        $volumesLabel = $totalVolumes > 0
            ? rtrim(rtrim(number_format($totalVolumes, 3, ',', '.'), '0'), ',')
            : '—';

        $weightLabel = $totalWeight > 0
            ? number_format($totalWeight, 3, ',', '.') . ' kg'
            : '—';

        return [
            'volumes' => $volumesLabel,
            'weight' => $weightLabel,
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
