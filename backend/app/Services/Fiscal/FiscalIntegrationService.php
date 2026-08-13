<?php

namespace App\Services\Fiscal;

use App\Exceptions\FiscalException;
use App\Models\FiscalWebhookEvent;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiscalIntegrationService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function requestEmission(SaleOrder $order, int $userId): array
    {
        if ($order->status !== 'faturado') {
            throw FiscalException::orderNotBillable($order->status);
        }

        $tenant = Tenant::findOrFail($order->tenant_id);

        if (!$tenant->fiscal_integration_provider) {
            throw FiscalException::providerNotConfigured();
        }

        return DB::transaction(function () use ($order, $tenant, $userId) {
            $order->update([
                'nfe_status'   => 'pending',
                'nfe_series'   => $tenant->nfe_series ?? '1',
            ]);

            $payload = $this->buildProviderPayload($order, $tenant);

            $this->auditService->log(
                $order->tenant_id,
                $userId,
                'fiscal.emission_requested',
                'sale_order',
                $order->id,
                ['provider' => $tenant->fiscal_integration_provider]
            );

            return [
                'sale_order_id' => $order->id,
                'provider'      => $tenant->fiscal_integration_provider,
                'status'        => 'pending',
                'payload'       => $payload,
                'note'          => 'Envie este payload ao provedor fiscal configurado. O retorno chega via webhook.',
            ];
        });
    }

    public function processWebhook(int $tenantId, Request $request): FiscalWebhookEvent
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->assertWebhookToken($tenant, $request);

        $data = $request->all();
        $saleOrderId = (int) ($data['sale_order_id'] ?? $data['reference_id'] ?? 0);
        $status      = strtolower((string) ($data['status'] ?? $data['nfe_status'] ?? ''));

        return DB::transaction(function () use ($tenant, $tenantId, $data, $saleOrderId, $status, $request) {
            $event = FiscalWebhookEvent::create([
                'tenant_id'     => $tenantId,
                'sale_order_id' => $saleOrderId ?: null,
                'provider'      => $tenant->fiscal_integration_provider,
                'event_type'    => $data['event'] ?? $data['event_type'] ?? 'nfe.status',
                'status'        => $status,
                'payload'       => $data,
            ]);

            if ($saleOrderId) {
                $order = SaleOrder::forTenant($tenantId)->find($saleOrderId);
                if ($order) {
                    $this->applyNfeUpdate($order, $data, $status);
                    $this->auditService->log(
                        $tenantId,
                        null,
                        'fiscal.webhook_received',
                        'sale_order',
                        $order->id,
                        ['status' => $status, 'event_id' => $event->id]
                    );
                }
            }

            return $event;
        });
    }

    private function applyNfeUpdate(SaleOrder $order, array $data, string $status): void
    {
        $mapped = match ($status) {
            'authorized', 'autorizada', 'approved' => 'authorized',
            'rejected', 'rejeitada', 'denied'     => 'rejected',
            'cancelled', 'cancelada'               => 'cancelled',
            default                                => $status ?: $order->nfe_status,
        };

        $order->update([
            'nfe_status'    => $mapped,
            'nfe_number'    => $data['nfe_number'] ?? $data['number'] ?? $order->nfe_number,
            'nfe_key'       => $data['nfe_key'] ?? $data['access_key'] ?? $order->nfe_key,
            'nfe_series'    => $data['nfe_series'] ?? $data['series'] ?? $order->nfe_series,
            'nfe_issued_at' => in_array($mapped, ['authorized'], true) ? now() : $order->nfe_issued_at,
        ]);
    }

    private function assertWebhookToken(Tenant $tenant, Request $request): void
    {
        $secret = $tenant->fiscal_integration_config['webhook_secret'] ?? null;
        if (!$secret) {
            return;
        }

        $token = $request->header('X-Fiscal-Webhook-Token')
            ?? $request->header('X-Webhook-Token');

        if (!hash_equals($secret, (string) $token)) {
            throw FiscalException::invalidWebhookToken();
        }
    }

    private function buildProviderPayload(SaleOrder $order, Tenant $tenant): array
    {
        $order->load(['client', 'items.product']);

        return [
            'reference_id'   => $order->id,
            'order_identify' => $order->identify,
            'series'         => $tenant->nfe_series ?? '1',
            'customer'       => [
                'name'  => $order->client?->company_name ?? $order->client?->name,
                'cnpj'  => $order->client?->cnpj,
                'email' => $order->client?->email,
            ],
            'items'          => $order->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'sku'        => $i->product?->sku,
                'name'       => $i->product?->name,
                'quantity'   => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'ncm'        => $i->product?->tax_ncm,
            ])->values()->all(),
            'total'          => (float) $order->total,
        ];
    }
}
