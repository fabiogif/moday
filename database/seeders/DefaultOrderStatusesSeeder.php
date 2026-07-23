<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Fluxo operacional padrão de pedidos:
 * Pendente → Aceito → Preparo → Entrega → Concluído (+ Cancelado)
 *
 * Mantém códigos de integração 99Food e remapeia slugs legados.
 */
class DefaultOrderStatusesSeeder extends Seeder
{
    /**
     * Slugs antigos → slug canônico do novo fluxo.
     *
     * @var array<string, string>
     */
    public const LEGACY_SLUG_MAP = [
        'pedido-recebido' => 'pendente',
        'confirmado' => 'aceito',
        'em-preparacao' => 'preparo',
        'pronto-para-expedicao' => 'entrega',
        'aguardando-entregador' => 'entrega',
        'em-entrega' => 'entrega',
        'entregue' => 'concluido',
        // cancelado permanece
    ];

    /**
     * Nomes legados em orders.status → nome canônico.
     *
     * @var array<string, string>
     */
    public const LEGACY_NAME_MAP = [
        'Pedido Recebido' => 'Pendente',
        'Confirmado' => 'Aceito',
        'Em Preparação' => 'Preparo',
        'Em Preparo' => 'Preparo',
        'Preparando' => 'Preparo',
        'Pronto para Expedição' => 'Entrega',
        'Pronto' => 'Entrega',
        'Aguardando Entregador' => 'Entrega',
        'Em Entrega' => 'Entrega',
        'Saiu para entrega' => 'Entrega',
        'A Caminho' => 'Entrega',
        'Entregue' => 'Concluído',
        'Concluido' => 'Concluído',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Pendente',
                'slug' => 'pendente',
                'description' => 'Pedido registrado, aguardando aceite.',
                'color' => '#3B82F6',
                'icon' => 'clock',
                'order_position' => 1,
                'is_initial' => true,
                'is_final' => false,
                'integration_codes' => [
                    '99food' => ['PENDING', 'WAITING_MERCHANT_CONFIRM'],
                ],
            ],
            [
                'name' => 'Aceito',
                'slug' => 'aceito',
                'description' => 'Pedido aceito e confirmado pelo estabelecimento.',
                'color' => '#6366F1',
                'icon' => 'check',
                'order_position' => 2,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    '99food' => ['MERCHANT_CONFIRMED', 'CONFIRMED'],
                ],
            ],
            [
                'name' => 'Preparo',
                'slug' => 'preparo',
                'description' => 'Pedido em preparação.',
                'color' => '#F59E0B',
                'icon' => 'chef-hat',
                'order_position' => 3,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    '99food' => ['PREPARING', 'IN_PREPARATION'],
                ],
            ],
            [
                'name' => 'Entrega',
                'slug' => 'entrega',
                'description' => 'Pedido em rota de entrega ou pronto para retirada.',
                'color' => '#60A5FA',
                'icon' => 'truck',
                'order_position' => 4,
                'is_initial' => false,
                'is_final' => false,
                'integration_codes' => [
                    '99food' => [
                        'READY_FOR_PICKUP',
                        'READY',
                        'RIDER_ACCEPTED',
                        'WAITING_RIDER',
                        'DELIVERING',
                        'ON_THE_WAY',
                    ],
                ],
            ],
            [
                'name' => 'Concluído',
                'slug' => 'concluido',
                'description' => 'Pedido concluído com sucesso.',
                'color' => '#10B981',
                'icon' => 'check-circle-2',
                'order_position' => 5,
                'is_initial' => false,
                'is_final' => true,
                'integration_codes' => [
                    '99food' => ['DELIVERED', 'COMPLETED', 'FINISHED'],
                ],
            ],
            [
                'name' => 'Cancelado',
                'slug' => 'cancelado',
                'description' => 'Pedido cancelado pelo cliente, estabelecimento ou marketplace.',
                'color' => '#EF4444',
                'icon' => 'x-circle',
                'order_position' => 6,
                'is_initial' => false,
                'is_final' => true,
                'integration_codes' => [
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
            $this->remapOrderStatusNames((int) $tenant->id);
        }

        $this->command?->info('Status de pedidos sincronizados com sucesso.');
    }

    /**
     * @param  list<array<string, mixed>>  $definitions
     */
    protected function syncTenantStatuses(int $tenantId, array $definitions): void
    {
        $this->command?->info("Tenant #{$tenantId}");

        $canonicalSlugs = array_column($definitions, 'slug');
        $slugToId = [];

        foreach ($definitions as $definition) {
            $status = $this->resolveOrCreateStatus($tenantId, $definition);
            $slugToId[$definition['slug']] = $status->id;
            $this->command?->info("  ✓ {$definition['name']} ({$definition['slug']})");
        }

        // Remapear pedidos ligados a status legados e desativar sobras
        $legacyStatuses = OrderStatus::query()
            ->where('tenant_id', $tenantId)
            ->whereNotIn('slug', $canonicalSlugs)
            ->get();

        foreach ($legacyStatuses as $legacy) {
            $targetSlug = self::LEGACY_SLUG_MAP[$legacy->slug] ?? null;
            $targetId = $targetSlug ? ($slugToId[$targetSlug] ?? null) : null;

            if ($targetId) {
                Order::query()
                    ->where('tenant_id', $tenantId)
                    ->where('order_status_id', $legacy->id)
                    ->update(['order_status_id' => $targetId]);

                $targetName = collect($definitions)->firstWhere('slug', $targetSlug)['name'] ?? null;
                if ($targetName) {
                    Order::query()
                        ->where('tenant_id', $tenantId)
                        ->where('order_status_id', $targetId)
                        ->where('status', $legacy->name)
                        ->update(['status' => $targetName]);
                }
            }

            $legacy->update(['is_active' => false, 'is_initial' => false]);
            $this->command?->info("  ⊘ Desativado legado: {$legacy->name} ({$legacy->slug})");
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function resolveOrCreateStatus(int $tenantId, array $definition): OrderStatus
    {
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

        if (! $status) {
            // Preferir renomear o primeiro legado que aponta para este slug
            $legacySlug = array_search($definition['slug'], self::LEGACY_SLUG_MAP, true);
            if ($legacySlug !== false) {
                $status = OrderStatus::query()
                    ->where('tenant_id', $tenantId)
                    ->where('slug', $legacySlug)
                    ->first();
            }
        }

        if ($definition['is_initial']) {
            OrderStatus::query()
                ->where('tenant_id', $tenantId)
                ->when($status, fn ($q) => $q->where('id', '!=', $status->id))
                ->update(['is_initial' => false]);
        }

        if ($status) {
            $status->update(array_merge($payload, [
                'slug' => $definition['slug'],
            ]));

            return $status->fresh();
        }

        return OrderStatus::query()->create(array_merge($payload, [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'slug' => $definition['slug'],
        ]));
    }

    protected function remapOrderStatusNames(int $tenantId): void
    {
        foreach (self::LEGACY_NAME_MAP as $from => $to) {
            $updated = Order::query()
                ->where('tenant_id', $tenantId)
                ->where('status', $from)
                ->update(['status' => $to]);

            if ($updated > 0) {
                $this->command?->info("  → {$updated} pedido(s): \"{$from}\" → \"{$to}\"");
            }
        }

        // Alinhar order_status_id pelo nome canônico
        $statuses = OrderStatus::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('slug', array_column(static::definitions(), 'slug'))
            ->get()
            ->keyBy('name');

        foreach ($statuses as $name => $status) {
            Order::query()
                ->where('tenant_id', $tenantId)
                ->where('status', $name)
                ->where(function ($q) use ($status) {
                    $q->whereNull('order_status_id')
                        ->orWhere('order_status_id', '!=', $status->id);
                })
                ->update(['order_status_id' => $status->id]);
        }
    }
}
