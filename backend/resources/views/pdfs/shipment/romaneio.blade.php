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
  .metric-cell { background:#f0fdf4;border:1px solid #d1fae5;border-radius:4px;padding:8px;text-align:center; }
  .metric-label { font-size:9px;color:#6b7280; }
  .metric-value { font-size:13px;font-weight:bold;color:#15803d; }
  .violation { color:#dc2626; }
  .empty { color:#6b7280; }
  .occ-th { border:1px solid #d1d5db;text-align:left; }
  .occ-td { border:1px solid #d1d5db; }
</style>
</head>
<body>
<div class="header">
  <div class="header-left">
    <h1>Romaneio de Entrega</h1>
    <div style="font-size:12px;margin-top:2px">{{ $identify }} · {{ $status }}</div>
  </div>
  <div class="header-right">
    {{ $generatedAt }}
  </div>
</div>

<div class="meta">
  <div class="meta-grid">
    <div class="meta-cell">
      <div class="meta-label">Rota</div>
      <div class="meta-value">{{ $routeName }}</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Motorista</div>
      <div class="meta-value">{{ $driverName }}</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Veículo / Placa</div>
      <div class="meta-value">{{ $vehiclePlate }}</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Paradas</div>
      <div class="meta-value">{{ $stopCount }}</div>
    </div>
  </div>
  <div class="meta-grid" style="margin-top:10px">
    <div class="meta-cell" style="width:100%">
      <div class="meta-label">Ordem da rota</div>
      <div class="meta-value">{{ $routeOrderSource }}</div>
    </div>
  </div>
</div>

<div class="section" style="padding-top:12px">
  <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:separate;border-spacing:4px">
    <tr>
      <td class="metric-cell">
        <div class="metric-label">Distância est.</div>
        <div class="metric-value">{{ $estimatedKm }}</div>
      </td>
      <td class="metric-cell">
        <div class="metric-label">Tempo est.</div>
        <div class="metric-value">{{ $estimatedTime }}</div>
      </td>
      <td class="metric-cell">
        <div class="metric-label">Peso total</div>
        <div class="metric-value">{{ $totalWeight }}</div>
      </td>
      <td class="metric-cell">
        <div class="metric-label">Volume total</div>
        <div class="metric-value">{{ $totalVolume }}</div>
      </td>
      <td class="metric-cell">
        <div class="metric-label">Frete Peso</div>
        <div class="metric-value">{{ $freightWeight }}</div>
      </td>
      <td class="metric-cell">
        <div class="metric-label">Tarifa FP</div>
        <div class="metric-value">{{ $freightUnit }}</div>
      </td>
    </tr>
  </table>
</div>

<div class="section">
  <h3 style="margin-bottom:8px;color:#374151;font-size:13px">
    Logística — ordem das entregas na rota
    <span style="font-weight:normal;color:#6b7280;font-size:11px">({{ $routeOrderSource }})</span>
  </h3>
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
      @forelse ($stops as $stop)
        <tr>
          <td style="text-align:center;font-weight:bold">{{ $stop['sequence'] }}</td>
          <td>{{ $stop['identify'] }}</td>
          <td>{{ $stop['client_name'] }}</td>
          <td>{{ $stop['city'] }}</td>
          <td>
            {{ $stop['window'] }}
            @if (!empty($stop['window_violation']))
              <span class="violation"> ⚠</span>
            @endif
          </td>
          <td>{{ $stop['eta'] }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center" class="empty">Nenhuma parada na rota</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="section">
  <h3 style="margin-bottom:8px;color:#374151;font-size:13px">Detalhes das entregas</h3>

  @forelse ($stops as $stop)
    <div class="stop-card">
      <div class="stop-title">Entrega #{{ $stop['sequence'] }} · {{ $stop['identify'] }}</div>
      <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;font-size:11px">
        <tr>
          <td style="width:50%;vertical-align:top;padding:4px 8px 4px 0">
            <div class="block-label">Dados do cliente</div>
            <div><strong>Nome / Razão social:</strong> {{ $stop['client_name'] }}</div>
            <div><strong>Telefone:</strong> {{ $stop['client_phone'] }}</div>
            <div style="margin-top:4px"><strong>Endereço:</strong> {{ $stop['address'] }}</div>
          </td>
          <td style="width:50%;vertical-align:top;padding:4px 0 4px 8px">
            <div class="block-label">Identificação do pedido</div>
            <div><strong>Pedido:</strong> {{ $stop['identify'] }}</div>
            <div><strong>Nota Fiscal:</strong> {{ $stop['nfe'] }}</div>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="padding:8px 0 0;vertical-align:top">
            <div class="block-label">Detalhes da carga</div>
            <div><strong>Volumes:</strong> {{ $stop['volumes'] }} &nbsp;·&nbsp; <strong>Peso:</strong> {{ $stop['weight'] }}</div>
            <div style="margin-top:2px"><strong>Produtos:</strong> {{ $stop['products'] }}</div>
          </td>
        </tr>
      </table>
    </div>
  @empty
    <p class="empty" style="font-size:11px">Nenhuma entrega vinculada a este romaneio.</p>
  @endforelse

  @if (count($occurrences) > 0)
    <h3 style="margin-top:24px;margin-bottom:8px;color:#374151;font-size:13px">Ocorrências</h3>
    <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:11px">
      <thead>
        <tr style="background:#fef3c7">
          <th class="occ-th">Tipo</th>
          <th class="occ-th">Descrição</th>
          <th class="occ-th">Data/Hora</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($occurrences as $occ)
          <tr>
            <td class="occ-td">{{ $occ['type'] }}</td>
            <td class="occ-td">{{ $occ['description'] }}</td>
            <td class="occ-td">{{ $occ['occurred_at'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

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
  <div class="generated">Gerado em {{ $generatedAt }} · DistribTec</div>
</div>
</body>
</html>
