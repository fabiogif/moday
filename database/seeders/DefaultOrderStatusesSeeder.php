<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Status padrão alinhados ao fluxo operacional e integrações de marketplace.
 *
 * Referências:
 * - iFood Merchant API (Order API): PLACED, CONFIRMED, PREPARATION_STARTED, READY_TO_PICKUP,
 *   DISPATCHED, CONCLUDED, CANCELLED
 * - 99Food / DiDi Food (merchant): aguardando confirmação, confirmado, em preparo, pronto,
 *   entregador a caminho, em entrega, concluído, cancelado
 */
class DefaultOrderStatusesSeeder extends Seeder
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Pedido Recebido',
                'slug' => 'pedido-recebido',
                'description' => 'Pedido registrado no sistema (balcão, garçom, app próprio ou marketplace).',
                'color' => '#3B82F6',
                'icon' => 'package',
                'order_position' => 1,
                'is_initial' => true,
                'is_final' => false,
                'integration_codes' => [
                    'ifood' => ['PLACED', 'WAITING', 'INTEGRATED'],
                    '99food' => ['PENDING', 'WAITING_MERCHANT_CONFIRM'],
                ],
            ],
            [
                'name' => 'Confirmado',
                'slug' => 'confirmado',
                'description' => 'Restaurante aceitou o pedido e iniciou o fluxo de produção.',
                'color' => '#6366F1',
                'icon' => 'check',
                'order_position' => 2,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    'ifood' => ['CONFIRMED'],
                    '99food' => ['MERCHANT_CONFIRMED', 'CONFIRMED'],
                ],
            ],
            [
                'name' => 'Em Preparação',
                'slug' => 'em-preparacao',
                'description' => 'Cozinha em produção (equivalente a PREPARATION_STARTED / PREPARING).',
                'color' => '#F59E0B',
                'icon' => 'chef-hat',
                'order_position' => 3,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    'ifood' => ['PREPARATION_STARTED', 'PREPARING'],
                    '99food' => ['PREPARING', 'IN_PREPARATION'],
                ],
            ],
            [
                'name' => 'Pronto para Expedição',
                'slug' => 'pronto-para-expedicao',
                'description' => 'Pedido finalizado na cozinha, aguardando retirada do entregador ou do cliente.',
                'color' => '#34D399',
                'icon' => 'check-circle',
                'order_position' => 4,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    'ifood' => ['READY_TO_PICKUP', 'READY_FOR_PICKUP'],
                    '99food' => ['READY_FOR_PICKUP', 'READY'],
                ],
            ],
            [
                'name' => 'Aguardando Entregador',
                'slug' => 'aguardando-entregador',
                'description' => 'Pedido despachado; aguardando aceite/coleta pelo entregador (logística).',
                'color' => '#8B5CF6',
                'icon' => 'user',
                'order_position' => 5,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    'ifood' => ['DISPATCHED'],
                    '99food' => ['RIDER_ACCEPTED', 'WAITING_RIDER'],
                ],
            ],
            [
                'name' => 'Em Entrega',
                'slug' => 'em-entrega',
                'description' => 'Pedido saiu para entrega (em rota até o cliente).',
                'color' => '#60A5FA',
                'icon' => 'truck',
                'order_position' => 6,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    'ifood' => ['OUT_FOR_DELIVERY', 'IN_DELIVERY'],
                    '99food' => ['DELIVERING', 'ON_THE_WAY'],
                ],
            ],
            [
                'name' => 'Entregue',
                'slug' => 'entregue',
                'description' => 'Pedido concluído com sucesso (entrega ou retirada no balcão).',
                'color' => '#10B981',
                'icon' => 'check-circle-2',
                'order_position' => 7,
                'is_initial' => false,
                'is_final' => true,
                'integration_codes' => [
                    'ifood' => ['DELIVERED', 'CONCLUDED', 'COMPLETED'],
                    '99food' => ['DELIVERED', 'COMPLETED', 'FINISHED'],
                ],
            ],
            [
                'name' => 'Cancelado',
                'slug' => 'cancelado',
                'description' => 'Pedido cancelado pelo cliente, restaurante ou marketplace.',
                'color' => '#EF4444',
                'icon' => 'x-circle',
                'order_position' => 8,
                'is_initial' => false,
                'is_final' => true,
                'integration_codes' => [
                    'ifood' => ['CANCELLED', 'CANCELLATION_REQUESTED'],
                    '99food' => ['CANCELLED', 'CANCELED', 'REFUNDED'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        $tenants = Tenant::query()->select('id')->get();

        if ($tenants->isEmpty()) {
            $this->command?->warn('Nenhum tenant encontrado. Execute TenantsTableSeeder antes.');

            return;
        }

        $definitions = static::definitions();
        $this->command?->info('Sincronizando '.count($definitions).' status para '.$tenants->count().' tenant(s)...');

        foreach ($tenants as $tenant) {
            $this->syncTenantStatuses((int) $tenant->id, $definitions);
        }

        $this->command?->info('Status de pedidos sincronizados com sucesso.');
    }

    /**
     * @param  list<array<string, mixed>>  $definitions
     */
    protected function syncTenantStatuses(int $tenantId, array $definitions): void
    {
        $this->command?->info("Tenant #{$tenantId}");

        foreach ($definitions as $definition) {
            $payload = [
                'name' => $definition['name'],
                'description' => $definition['description'],
                'color' => $definition['color'],
                'icon' => $definition['icon'],
                'order_position' => $definition['order_position'],
                'is_initial' => $definition['is_initial'],
                'is_final' => $definition['is_final'],
                'is_active' => true,
                'integration_codes' => $definition['integration_codes'] ?? null,
            ];

            $status = OrderStatus::query()
                ->where('tenant_id', $tenantId)
                ->where('slug', $definition['slug'])
                ->first();

            if ($status) {
                // Garante um único status inicial por tenant
                if ($definition['is_initial']) {
                    OrderStatus::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', '!=', $status->id)
                        ->update(['is_initial' => false]);
                }

                $status->update($payload);
                $this->command?->info("  ↻ Atualizado: {$definition['name']}");

                continue;
            }

            if ($definition['is_initial']) {
                OrderStatus::query()
                    ->where('tenant_id', $tenantId)
                    ->update(['is_initial' => false]);
            }

            OrderStatus::query()->create(array_merge($payload, [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'slug' => $definition['slug'],
            ]));

            $this->command?->info("  + Criado: {$definition['name']}");
        }
    }
}
