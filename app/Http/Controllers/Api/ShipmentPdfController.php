<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                ->with(['saleOrders.client', 'vehicle', 'driver', 'carrier', 'occurrences'])
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
        $stopCount = $shipment->saleOrders->count();

        $stopsHtml = '';
        $optimizedRoute = is_array($shipment->optimized_route) ? $shipment->optimized_route : [];
        $ordersById = $shipment->saleOrders->keyBy('id');

        if (!empty($optimizedRoute)) {
            foreach ($optimizedRoute as $stop) {
                $seq = (int) ($stop['sequence'] ?? 0);
                $orderId = $stop['sale_order_id'] ?? null;
                $order = $orderId ? ($ordersById[$orderId] ?? null) : null;
                $orderIdentify = e($stop['identify'] ?? '—');
                $client = e($stop['client'] ?? ($order?->client?->company_name ?? $order?->client?->name ?? '—'));
                $eta = e($stop['eta'] ?? '—');
                $window = e($stop['window'] ?? '—');
                $city = e($order?->shipping_city ?? '—');
                $violation = !empty($stop['window_violation']) ? '<span style="color:#dc2626"> ⚠</span>' : '';
                $stopsHtml .= "<tr>
                    <td style='text-align:center'>{$seq}</td>
                    <td>{$orderIdentify}</td>
                    <td>{$client}</td>
                    <td>{$city}</td>
                    <td>{$window}{$violation}</td>
                    <td>{$eta}</td>
                </tr>";
            }
        } else {
            foreach ($shipment->saleOrders as $i => $order) {
                $seq = $i + 1;
                $orderIdentify = e($order->identify);
                $client = e($order->client?->company_name ?? $order->client?->trade_name ?? $order->client?->name ?? '—');
                $city = e($order->shipping_city ?? '—');
                $stopsHtml .= "<tr>
                    <td style='text-align:center'>{$seq}</td>
                    <td>{$orderIdentify}</td>
                    <td>{$client}</td>
                    <td>{$city}</td>
                    <td>—</td>
                    <td>—</td>
                </tr>";
            }
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
  .kpi-row { display: table; width: 100%; margin: 12px 0; }
  .kpi { display: table-cell; width: 16.6%; text-align: center; background: #f0fdf4; border-radius: 4px; padding: 8px 4px; border: 1px solid #d1fae5; }
  .kpi-label { font-size: 9px; color: #6b7280; }
  .kpi-value { font-size: 13px; font-weight: bold; color: #15803d; }
  .section { padding: 0 20px 16px; }
  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  th { background: #16a34a; color: #fff; padding: 6px 8px; text-align: left; }
  td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
  tr:nth-child(even) td { background: #f9fafb; }
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
    </tr>
  </table>
</div>

<div class="section">
  <h3 style="margin-bottom:8px;color:#374151;font-size:13px">Paradas da rota</h3>
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
      {$stopsHtml}
    </tbody>
  </table>

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
}
