<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gera massa de dados de teste (clientes, produtos, pedidos de venda)
 * para o tenant de um usuário específico.
 *
 * Marcador: notes/sku com SEED-MASS — permite limpar com --fresh.
 */
class SeedMassTestDataCommand extends Command
{
    protected $signature = 'demo:seed-mass
                            {--email=contato@albatec.com.br : Email do usuário dono do tenant}
                            {--clients=10000 : Quantidade de clientes}
                            {--products=5000 : Quantidade de produtos}
                            {--orders=10000 : Quantidade de pedidos de venda (sale_orders)}
                            {--chunk=500 : Tamanho do lote de insert}
                            {--fresh : Remove massa SEED-MASS anterior do tenant antes de inserir}
                            {--bootstrap : Cria tenant+usuário mínimos se o email não existir (útil no local)}
                            {--force : Confirma execução sem prompt interativo}';

    protected $description = 'Insere massa de teste (clientes/produtos/pedidos) no tenant do usuário informado';

    private const MARKER = 'SEED-MASS';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $clientsTarget = max(0, (int) $this->option('clients'));
        $productsTarget = max(0, (int) $this->option('products'));
        $ordersTarget = max(0, (int) $this->option('orders'));
        $chunk = max(50, min(1000, (int) $this->option('chunk')));

        $user = User::query()->where('email', $email)->first();

        if (!$user && $this->option('bootstrap')) {
            $user = $this->bootstrapUser($email);
        }

        if (!$user) {
            $this->error("Usuário não encontrado: {$email}");
            $this->line('Dica: use --bootstrap no ambiente local vazio, ou confira o email (ex.: contato@albatec.com.br).');

            return self::FAILURE;
        }

        if (!$user->tenant_id) {
            $this->error("Usuário {$email} sem tenant_id.");

            return self::FAILURE;
        }

        $tenant = Tenant::query()->find($user->tenant_id);
        if (!$tenant) {
            $this->error("Tenant #{$user->tenant_id} não encontrado.");

            return self::FAILURE;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('sale_orders')) {
            $this->error('Tabela sale_orders inexistente neste banco. Rode as migrations antes.');

            return self::FAILURE;
        }

        $this->info("Tenant #{$tenant->id} — {$tenant->name}");
        $this->info("Usuário #{$user->id} — {$user->email}");
        $this->table(
            ['Recurso', 'Quantidade'],
            [
                ['Clientes', $clientsTarget],
                ['Produtos', $productsTarget],
                ['Pedidos (sale_orders)', $ordersTarget],
                ['Chunk', $chunk],
            ]
        );

        if (!$this->option('force') && !$this->confirm('Continuar com a inserção?', true)) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $started = microtime(true);

        if ($this->option('fresh')) {
            $this->purgePreviousSeed((int) $tenant->id);
        }

        DB::connection()->disableQueryLog();

        $clientIds = $this->seedClients((int) $tenant->id, $clientsTarget, $chunk);
        $productIds = $this->seedProducts((int) $tenant->id, $productsTarget, $chunk);
        $ordersCreated = $this->seedOrders(
            (int) $tenant->id,
            $ordersTarget,
            $chunk,
            $clientIds,
            $productIds,
            (int) $user->id
        );

        $elapsed = round(microtime(true) - $started, 1);

        $this->newLine();
        $this->info("Concluído em {$elapsed}s");
        $this->table(
            ['Recurso', 'No tenant (total)', 'Criados nesta execução'],
            [
                [
                    'Clientes',
                    DB::table('clients')->where('tenant_id', $tenant->id)->count(),
                    count($clientIds),
                ],
                [
                    'Produtos',
                    DB::table('products')->where('tenant_id', $tenant->id)->whereNull('deleted_at')->count(),
                    count($productIds),
                ],
                [
                    'Pedidos',
                    DB::table('sale_orders')->where('tenant_id', $tenant->id)->count(),
                    $ordersCreated,
                ],
            ]
        );

        return self::SUCCESS;
    }

    private function bootstrapUser(string $email): User
    {
        $this->warn('Bootstrap: criando tenant + usuário locais…');

        $slug = 'albatec-seed-'.Str::lower(Str::random(6));
        $now = now()->toDateTimeString();

        $tenantRow = [
            'uuid' => (string) Str::uuid(),
            'name' => 'Nova Bahia medicamentos (local seed)',
            'slug' => $slug,
            'email' => $email,
            'url' => $slug.'.local',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('tenants', 'subdomain')) {
            $tenantRow['subdomain'] = $slug;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('tenants', 'account_status')) {
            $tenantRow['account_status'] = 'active';
        }

        $tenantId = DB::table('tenants')->insertGetId($tenantRow);

        $userRow = [
            'name' => 'Contato Albatec Seed',
            'email' => $email,
            'password' => Hash::make('password'),
            'tenant_id' => $tenantId,
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active')) {
            $userRow['is_active'] = 1;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'uuid')) {
            $userRow['uuid'] = (string) Str::uuid();
        }

        $userId = DB::table('users')->insertGetId($userRow);

        return User::query()->findOrFail($userId);
    }

    private function purgePreviousSeed(int $tenantId): void
    {
        $this->warn('Removendo massa SEED-MASS anterior…');

        $orderIds = DB::table('sale_orders')
            ->where('tenant_id', $tenantId)
            ->where('notes', self::MARKER)
            ->pluck('id');

        if ($orderIds->isNotEmpty()) {
            foreach ($orderIds->chunk(1000) as $ids) {
                DB::table('sale_order_items')->whereIn('sale_order_id', $ids)->delete();
                DB::table('sale_orders')->whereIn('id', $ids)->delete();
            }
        }

        DB::table('products')
            ->where('tenant_id', $tenantId)
            ->where('sku', 'like', self::MARKER.'-%')
            ->delete();

        $clientsQuery = DB::table('clients')->where('tenant_id', $tenantId);
        if (\Illuminate\Support\Facades\Schema::hasColumn('clients', 'notes')) {
            $clientsQuery->where('notes', self::MARKER);
        } else {
            $clientsQuery->where('email', 'like', 'seed.client.%@example.test');
        }
        $clientsQuery->delete();

        $this->info('Massa anterior removida.');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function onlyExistingColumns(string $table, array $row): array
    {
        static $columns = [];

        if (! isset($columns[$table])) {
            $columns[$table] = array_flip(\Illuminate\Support\Facades\Schema::getColumnListing($table));
        }

        return array_filter(
            $row,
            static fn ($key) => isset($columns[$table][$key]),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return list<int>
     */
    private function seedClients(int $tenantId, int $target, int $chunk): array
    {
        if ($target <= 0) {
            return DB::table('clients')->where('tenant_id', $tenantId)->pluck('id')->all();
        }

        $this->info("Inserindo {$target} clientes…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $now = now()->toDateTimeString();
        // Um único hash — bcrypt por linha tornaria o seed inviável.
        $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password
        $states = ['SP', 'RJ', 'MG', 'BA', 'PR', 'RS', 'PE', 'CE', 'GO', 'SC'];
        $types = ['farmacia', 'drogaria', 'supermercado', 'hospital', 'outro'];
        $createdIds = [];
        $seq = 1;

        while ($seq <= $target) {
            $batch = [];
            $batchSize = min($chunk, $target - $seq + 1);

            for ($i = 0; $i < $batchSize; $i++, $seq++) {
                $uuid = (string) Str::uuid();
                $batch[] = $this->onlyExistingColumns('clients', [
                    'tenant_id' => $tenantId,
                    'uuid' => $uuid,
                    'name' => sprintf('Cliente Seed %05d', $seq),
                    'company_name' => sprintf('Cliente Seed %05d LTDA', $seq),
                    'trade_name' => sprintf('Seed %05d', $seq),
                    'cnpj' => $this->fakeCnpj($tenantId, $seq),
                    'email' => sprintf('seed.client.%d.%d@example.test', $tenantId, $seq),
                    'phone' => sprintf('(71) 9%08d', $seq % 100000000),
                    'city' => 'Salvador',
                    'state' => $states[$seq % count($states)],
                    'client_type' => $types[$seq % count($types)],
                    'credit_limit' => ($seq % 10) * 1000,
                    'payment_term_days' => [0, 30, 60, 90][$seq % 4],
                    'is_active' => 1,
                    'is_blocked' => 0,
                    'password' => $password,
                    'notes' => self::MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('clients')->insert($batch);

            $emails = array_column($batch, 'email');
            $ids = DB::table('clients')
                ->where('tenant_id', $tenantId)
                ->whereIn('email', $emails)
                ->pluck('id')
                ->all();
            array_push($createdIds, ...$ids);

            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();

        return $createdIds;
    }

    /**
     * @return list<int>
     */
    private function seedProducts(int $tenantId, int $target, int $chunk): array
    {
        if ($target <= 0) {
            return DB::table('products')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();
        }

        $this->info("Inserindo {$target} produtos…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $now = now()->toDateTimeString();
        $createdIds = [];
        $seq = 1;

        while ($seq <= $target) {
            $batch = [];
            $batchSize = min($chunk, $target - $seq + 1);

            for ($i = 0; $i < $batchSize; $i++, $seq++) {
                $price = round(5 + ($seq % 200) + (($seq % 7) * 0.13), 2);
                $cost = round($price * 0.55, 2);
                $batch[] = $this->onlyExistingColumns('products', [
                    'tenant_id' => $tenantId,
                    'uuid' => (string) Str::uuid(),
                    'name' => sprintf('Produto Seed %05d', $seq),
                    'flag' => sprintf('produto-seed-%d-%05d', $tenantId, $seq),
                    'description' => 'Produto de massa de teste '.self::MARKER,
                    'sku' => sprintf('%s-%d-%05d', self::MARKER, $tenantId, $seq),
                    'brand' => 'SeedBrand',
                    'price' => $price,
                    'price_cost' => $cost,
                    'qtd_stock' => 100 + ($seq % 500),
                    'is_active' => 1,
                    'product_type' => 'medicamento',
                    'unit_of_measure' => 'un',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('products')->insert($batch);

            $skus = array_column($batch, 'sku');
            $ids = DB::table('products')
                ->where('tenant_id', $tenantId)
                ->whereIn('sku', $skus)
                ->pluck('id')
                ->all();
            array_push($createdIds, ...$ids);

            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();

        return $createdIds;
    }

    /**
     * @param  list<int>  $clientIds
     * @param  list<int>  $productIds
     */
    private function seedOrders(
        int $tenantId,
        int $target,
        int $chunk,
        array $clientIds,
        array $productIds,
        int $userId
    ): int {
        if ($target <= 0) {
            return 0;
        }

        if ($clientIds === [] || $productIds === []) {
            $this->error('Não há clientes/produtos suficientes para criar pedidos.');

            return 0;
        }

        $this->info("Inserindo {$target} pedidos…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $statuses = array_keys(\App\Models\SaleOrder::STATUSES);
        $clientCount = count($clientIds);
        $productCount = count($productIds);
        $created = 0;
        $seq = 1;

        while ($seq <= $target) {
            $batchSize = min($chunk, $target - $seq + 1);
            $orderRows = [];
            $itemPlans = [];

            for ($i = 0; $i < $batchSize; $i++, $seq++) {
                $uuid = (string) Str::uuid();
                $clientId = $clientIds[($seq - 1) % $clientCount];
                $status = $statuses[($seq - 1) % count($statuses)];
                $itemCount = 1 + (($seq - 1) % 3);
                $subtotal = 0.0;
                $items = [];

                for ($j = 0; $j < $itemCount; $j++) {
                    $productId = $productIds[(($seq - 1) * 3 + $j) % $productCount];
                    $qty = 1 + (($seq + $j) % 5);
                    $unitPrice = round(10 + (($seq + $j) % 90) + 0.5, 4);
                    $line = round($qty * $unitPrice, 2);
                    $subtotal += $line;
                    $items[] = [
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $line,
                    ];
                }

                $orderedAt = now()->subDays(($seq - 1) % 180)->subMinutes($seq % 1440);

                $orderRows[] = $this->onlyExistingColumns('sale_orders', [
                    'tenant_id' => $tenantId,
                    'uuid' => $uuid,
                    'identify' => sprintf('PV-SEED-%d-%06d', $tenantId, $seq),
                    'client_id' => $clientId,
                    'approved_by' => in_array($status, ['aprovado', 'separacao', 'faturado', 'em_transito', 'entregue'], true)
                        ? $userId
                        : null,
                    'status' => $status,
                    'type' => 'venda',
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'freight_amount' => ($seq % 5 === 0) ? 15.90 : 0,
                    'total' => round($subtotal + (($seq % 5 === 0) ? 15.90 : 0), 2),
                    'payment_term_days' => [0, 30, 60][$seq % 3],
                    'payment_method' => ['boleto', 'pix', 'cartao', 'dinheiro'][$seq % 4],
                    'ordered_at' => $orderedAt->toDateTimeString(),
                    'approved_at' => in_array($status, ['aprovado', 'separacao', 'faturado', 'em_transito', 'entregue'], true)
                        ? $orderedAt->copy()->addHour()->toDateTimeString()
                        : null,
                    'notes' => self::MARKER,
                    'created_at' => $orderedAt->toDateTimeString(),
                    'updated_at' => $orderedAt->toDateTimeString(),
                ]);
                $itemPlans[$uuid] = $items;
            }

            DB::table('sale_orders')->insert($orderRows);

            $uuids = array_column($orderRows, 'uuid');
            $orderIdByUuid = DB::table('sale_orders')
                ->where('tenant_id', $tenantId)
                ->whereIn('uuid', $uuids)
                ->pluck('id', 'uuid');

            $itemRows = [];
            foreach ($itemPlans as $uuid => $items) {
                $orderId = $orderIdByUuid[$uuid] ?? null;
                if (!$orderId) {
                    continue;
                }
                foreach ($items as $item) {
                    $itemRows[] = [
                        'sale_order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'item_type' => 'venda',
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => 0,
                        'subtotal' => $item['subtotal'],
                        'tax_amount' => 0,
                    ];
                }
            }

            foreach (array_chunk($itemRows, $chunk) as $itemChunk) {
                DB::table('sale_order_items')->insert($itemChunk);
            }

            $created += count($orderRows);
            $bar->advance(count($orderRows));
        }

        $bar->finish();
        $this->newLine();

        return $created;
    }

    private function fakeCnpj(int $tenantId, int $seq): string
    {
        // Formato visual apenas; único por tenant+seq para evitar colisão em seed repetido.
        $base = sprintf('%08d%04d', $tenantId % 100000000, $seq % 10000);

        return substr($base, 0, 2).'.'.substr($base, 2, 3).'.'.substr($base, 5, 3).'/0001-'.str_pad((string) ($seq % 100), 2, '0', STR_PAD_LEFT);
    }
}
