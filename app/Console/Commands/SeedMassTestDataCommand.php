<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Gera massa de dados de teste (clientes, produtos, categorias,
 * pedidos de venda e pedidos de compra) para o tenant de um usuário.
 *
 * Marcador: notes/sku/description/url com SEED-MASS — limpa com --fresh.
 */
class SeedMassTestDataCommand extends Command
{
    protected $signature = 'demo:seed-mass
                            {--email=contato@albatec.com.br : Email do usuário dono do tenant}
                            {--clients=10000 : Quantidade de clientes a criar nesta execução}
                            {--products=0 : Quantidade de produtos a criar (0 = reusa existentes)}
                            {--orders=10000 : Quantidade de pedidos de venda (sale_orders)}
                            {--purchase-orders=0 : Quantidade de pedidos de compra}
                            {--categories=0 : Quantidade de categorias}
                            {--suppliers=10 : Fornecedores SEED criados se faltarem (para purchase orders)}
                            {--chunk=500 : Tamanho do lote de insert}
                            {--fresh : Remove massa SEED-MASS anterior do tenant antes de inserir}
                            {--bootstrap : Cria tenant+usuário mínimos se o email não existir (útil no local)}
                            {--force : Confirma execução sem prompt interativo}';

    protected $description = 'Insere massa de teste (clientes/produtos/categorias/pedidos) no tenant do usuário informado';

    private const MARKER = 'SEED-MASS';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $clientsTarget = max(0, (int) $this->option('clients'));
        $productsTarget = max(0, (int) $this->option('products'));
        $ordersTarget = max(0, (int) $this->option('orders'));
        $purchaseOrdersTarget = max(0, (int) $this->option('purchase-orders'));
        $categoriesTarget = max(0, (int) $this->option('categories'));
        $suppliersTarget = max(1, (int) $this->option('suppliers'));
        $chunk = max(50, min(1000, (int) $this->option('chunk')));

        $user = User::query()->where('email', $email)->first();

        if (! $user && $this->option('bootstrap')) {
            $user = $this->bootstrapUser($email);
        }

        if (! $user) {
            $this->error("Usuário não encontrado: {$email}");
            $this->line('Dica: use --bootstrap no ambiente local vazio, ou confira o email (ex.: contato@albatec.com.br).');

            return self::FAILURE;
        }

        if (! $user->tenant_id) {
            $this->error("Usuário {$email} sem tenant_id.");

            return self::FAILURE;
        }

        $tenant = Tenant::query()->find($user->tenant_id);
        if (! $tenant) {
            $this->error("Tenant #{$user->tenant_id} não encontrado.");

            return self::FAILURE;
        }

        if ($ordersTarget > 0 && ! Schema::hasTable('sale_orders')) {
            $this->error('Tabela sale_orders inexistente neste banco. Rode as migrations antes.');

            return self::FAILURE;
        }

        if ($purchaseOrdersTarget > 0 && ! Schema::hasTable('purchase_orders')) {
            $this->error('Tabela purchase_orders inexistente neste banco. Rode as migrations antes.');

            return self::FAILURE;
        }

        $this->info("Tenant #{$tenant->id} — {$tenant->name}");
        $this->info("Usuário #{$user->id} — {$user->email}");
        $this->table(
            ['Recurso', 'Quantidade nesta execução'],
            [
                ['Clientes', $clientsTarget],
                ['Produtos', $productsTarget],
                ['Categorias', $categoriesTarget],
                ['Pedidos venda (sale_orders)', $ordersTarget],
                ['Pedidos compra (purchase_orders)', $purchaseOrdersTarget],
                ['Chunk', $chunk],
            ]
        );

        if (! $this->option('force') && ! $this->confirm('Continuar com a inserção?', true)) {
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
        $categoryIds = $this->seedCategories((int) $tenant->id, $categoriesTarget, $chunk, $productIds);
        $supplierIds = $purchaseOrdersTarget > 0
            ? $this->ensureSeedSuppliers((int) $tenant->id, $suppliersTarget)
            : [];
        $ordersCreated = $this->seedOrders(
            (int) $tenant->id,
            $ordersTarget,
            $chunk,
            $clientIds,
            $productIds,
            (int) $user->id
        );
        $purchaseOrdersCreated = $this->seedPurchaseOrders(
            (int) $tenant->id,
            $purchaseOrdersTarget,
            $chunk,
            $supplierIds,
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
                    $clientsTarget,
                ],
                [
                    'Produtos',
                    DB::table('products')->where('tenant_id', $tenant->id)->whereNull('deleted_at')->count(),
                    $productsTarget,
                ],
                [
                    'Categorias',
                    DB::table('categories')->where('tenant_id', $tenant->id)->count(),
                    count($categoryIds),
                ],
                [
                    'Pedidos venda',
                    DB::table('sale_orders')->where('tenant_id', $tenant->id)->count(),
                    $ordersCreated,
                ],
                [
                    'Pedidos compra',
                    Schema::hasTable('purchase_orders')
                        ? DB::table('purchase_orders')->where('tenant_id', $tenant->id)->count()
                        : 0,
                    $purchaseOrdersCreated,
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

        if (Schema::hasColumn('tenants', 'subdomain')) {
            $tenantRow['subdomain'] = $slug;
        }
        if (Schema::hasColumn('tenants', 'account_status')) {
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

        if (Schema::hasColumn('users', 'is_active')) {
            $userRow['is_active'] = 1;
        }
        if (Schema::hasColumn('users', 'uuid')) {
            $userRow['uuid'] = (string) Str::uuid();
        }

        $userId = DB::table('users')->insertGetId($userRow);

        return User::query()->findOrFail($userId);
    }

    private function purgePreviousSeed(int $tenantId): void
    {
        $this->warn('Removendo massa SEED-MASS anterior…');

        if (Schema::hasTable('purchase_orders')) {
            $poIds = DB::table('purchase_orders')
                ->where('tenant_id', $tenantId)
                ->where('notes', self::MARKER)
                ->pluck('id');

            if ($poIds->isNotEmpty()) {
                foreach ($poIds->chunk(1000) as $ids) {
                    if (Schema::hasTable('purchase_order_items')) {
                        DB::table('purchase_order_items')->whereIn('purchase_order_id', $ids)->delete();
                    }
                    DB::table('purchase_orders')->whereIn('id', $ids)->delete();
                }
            }
        }

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
        if (Schema::hasColumn('clients', 'notes')) {
            $clientsQuery->where('notes', self::MARKER);
        } else {
            $clientsQuery->where('email', 'like', 'seed.client.%@example.test');
        }
        $clientsQuery->delete();

        if (Schema::hasTable('categories')) {
            DB::table('categories')
                ->where('tenant_id', $tenantId)
                ->where(function ($q) {
                    $q->where('description', 'like', '%'.self::MARKER.'%')
                        ->orWhere('url', 'like', 'seed-cat-%')
                        ->orWhere('slug', 'like', 'seed-cat-%');
                })
                ->delete();
        }

        if (Schema::hasTable('suppliers')) {
            $suppliersQuery = DB::table('suppliers')->where('tenant_id', $tenantId);
            if (Schema::hasColumn('suppliers', 'notes')) {
                $suppliersQuery->where('notes', self::MARKER);
            } else {
                $suppliersQuery->where('email', 'like', 'seed.supplier.%@example.test');
            }
            $suppliersQuery->delete();
        }

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
            $columns[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_filter(
            $row,
            static fn ($key) => isset($columns[$table][$key]),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function nextClientSeq(int $tenantId): int
    {
        $count = DB::table('clients')
            ->where('tenant_id', $tenantId)
            ->where('email', 'like', 'seed.client.%@example.test')
            ->count();

        return $count + 1;
    }

    private function nextSaleOrderSeq(int $tenantId): int
    {
        $count = DB::table('sale_orders')
            ->where('tenant_id', $tenantId)
            ->where('identify', 'like', 'PV-SEED-'.$tenantId.'-%')
            ->count();

        return $count + 1;
    }

    private function nextPurchaseOrderSeq(int $tenantId): int
    {
        $count = DB::table('purchase_orders')
            ->where('tenant_id', $tenantId)
            ->where('identify', 'like', 'PC-SEED-'.$tenantId.'-%')
            ->count();

        return $count + 1;
    }

    private function nextCategorySeq(int $tenantId): int
    {
        $count = DB::table('categories')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('url', 'like', 'seed-cat-%')
                    ->orWhere('slug', 'like', 'seed-cat-%');
            })
            ->count();

        return $count + 1;
    }

    /**
     * @return list<int>
     */
    private function seedClients(int $tenantId, int $target, int $chunk): array
    {
        if ($target <= 0) {
            return DB::table('clients')->where('tenant_id', $tenantId)->pluck('id')->all();
        }

        $seq = $this->nextClientSeq($tenantId);
        $end = $seq + $target - 1;

        $this->info("Inserindo {$target} clientes (seq {$seq}…{$end})…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $now = now()->toDateTimeString();
        // Um único hash — bcrypt por linha tornaria o seed inviável.
        $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password
        $states = ['SP', 'RJ', 'MG', 'BA', 'PR', 'RS', 'PE', 'CE', 'GO', 'SC'];
        $types = ['farmacia', 'drogaria', 'supermercado', 'hospital', 'outro'];
        $createdIds = [];
        $remaining = $target;

        while ($remaining > 0) {
            $batch = [];
            $batchSize = min($chunk, $remaining);

            for ($i = 0; $i < $batchSize; $i++, $seq++, $remaining--) {
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

        // Pedidos precisam de todos os clientes do tenant, não só os novos.
        return DB::table('clients')->where('tenant_id', $tenantId)->pluck('id')->all();
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

        $existingSeed = DB::table('products')
            ->where('tenant_id', $tenantId)
            ->where('sku', 'like', self::MARKER.'-%')
            ->count();
        $seq = $existingSeed + 1;
        $end = $seq + $target - 1;

        $this->info("Inserindo {$target} produtos (seq {$seq}…{$end})…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $now = now()->toDateTimeString();
        $createdIds = [];
        $remaining = $target;

        while ($remaining > 0) {
            $batch = [];
            $batchSize = min($chunk, $remaining);

            for ($i = 0; $i < $batchSize; $i++, $seq++, $remaining--) {
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

        return DB::table('products')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $productIds
     * @return list<int>
     */
    private function seedCategories(int $tenantId, int $target, int $chunk, array $productIds): array
    {
        if ($target <= 0 || ! Schema::hasTable('categories')) {
            return [];
        }

        $seq = $this->nextCategorySeq($tenantId);
        $end = $seq + $target - 1;

        $this->info("Inserindo {$target} categorias (seq {$seq}…{$end})…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $now = now()->toDateTimeString();
        $createdIds = [];
        $remaining = $target;

        $pivotTable = Schema::hasTable('category_product')
            ? 'category_product'
            : (Schema::hasTable('product_category') ? 'product_category' : null);

        while ($remaining > 0) {
            $batch = [];
            $batchSize = min($chunk, $remaining);

            for ($i = 0; $i < $batchSize; $i++, $seq++, $remaining--) {
                $slug = sprintf('seed-cat-%d-%03d', $tenantId, $seq);
                $batch[] = $this->onlyExistingColumns('categories', [
                    'tenant_id' => $tenantId,
                    'uuid' => (string) Str::uuid(),
                    'name' => sprintf('Categoria Seed %03d', $seq),
                    'description' => 'Categoria de massa de teste '.self::MARKER,
                    'url' => $slug,
                    'slug' => $slug,
                    'status' => 'A',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('categories')->insert($batch);

            $urls = array_column($batch, 'url');
            $ids = DB::table('categories')
                ->where('tenant_id', $tenantId)
                ->whereIn('url', $urls)
                ->pluck('id')
                ->all();
            array_push($createdIds, ...$ids);

            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();

        if ($pivotTable && $productIds !== [] && $createdIds !== []) {
            $this->attachProductsToCategories($pivotTable, $createdIds, $productIds);
        }

        return $createdIds;
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $productIds
     */
    private function attachProductsToCategories(string $pivotTable, array $categoryIds, array $productIds): void
    {
        $this->info('Associando produtos às categorias seed…');

        $columns = array_flip(Schema::getColumnListing($pivotTable));
        $hasTimestamps = isset($columns['created_at']);
        $now = now()->toDateTimeString();
        $catCount = count($categoryIds);
        $rows = [];

        // Até 20 produtos por categoria, round-robin — suficiente para UI sem explodir a pivot.
        foreach ($categoryIds as $idx => $categoryId) {
            for ($j = 0; $j < 20; $j++) {
                $productId = $productIds[(($idx * 20) + $j) % count($productIds)];
                $row = [
                    'category_id' => $categoryId,
                    'product_id' => $productId,
                ];
                if ($hasTimestamps) {
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                }
                $rows[] = $row;
            }
        }

        foreach (array_chunk($rows, 500) as $chunkRows) {
            try {
                DB::table($pivotTable)->insertOrIgnore($chunkRows);
            } catch (\Throwable) {
                // Ambientes sem unique na pivot: tenta insert simples e ignora duplicata.
                try {
                    DB::table($pivotTable)->insert($chunkRows);
                } catch (\Throwable) {
                    // noop
                }
            }
        }
    }

    /**
     * @return list<int>
     */
    private function ensureSeedSuppliers(int $tenantId, int $target): array
    {
        if (! Schema::hasTable('suppliers')) {
            return [];
        }

        $existing = DB::table('suppliers')
            ->where('tenant_id', $tenantId)
            ->when(
                Schema::hasColumn('suppliers', 'notes'),
                fn ($q) => $q->where('notes', self::MARKER),
                fn ($q) => $q->where('email', 'like', 'seed.supplier.%@example.test')
            )
            ->pluck('id')
            ->all();

        if (count($existing) >= $target) {
            return $existing;
        }

        $need = $target - count($existing);
        $this->info("Inserindo {$need} fornecedores seed…");
        $now = now()->toDateTimeString();
        $start = count($existing) + 1;

        $batch = [];
        for ($seq = $start; $seq < $start + $need; $seq++) {
            $doc = sprintf('%014d', ($tenantId * 100000) + $seq);
            $batch[] = $this->onlyExistingColumns('suppliers', [
                'tenant_id' => $tenantId,
                'uuid' => (string) Str::uuid(),
                'name' => sprintf('Fornecedor Seed %03d', $seq),
                'fantasy_name' => sprintf('Forn Seed %03d', $seq),
                'company_name' => sprintf('Fornecedor Seed %03d LTDA', $seq),
                'trade_name' => sprintf('Forn Seed %03d', $seq),
                'document' => $doc,
                'document_type' => 'cnpj',
                'cnpj' => $this->fakeCnpj($tenantId + 9000, $seq),
                'email' => sprintf('seed.supplier.%d.%d@example.test', $tenantId, $seq),
                'phone' => sprintf('(71) 3%08d', $seq % 100000000),
                'city' => 'Salvador',
                'state' => 'BA',
                'payment_term_days' => 30,
                'lead_time_days' => 5,
                'is_active' => 1,
                'notes' => self::MARKER,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('suppliers')->insert($batch);

        return DB::table('suppliers')
            ->where('tenant_id', $tenantId)
            ->when(
                Schema::hasColumn('suppliers', 'notes'),
                fn ($q) => $q->where('notes', self::MARKER),
                fn ($q) => $q->where('email', 'like', 'seed.supplier.%@example.test')
            )
            ->pluck('id')
            ->all();
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
            $this->error('Não há clientes/produtos suficientes para criar pedidos de venda.');

            return 0;
        }

        $seq = $this->nextSaleOrderSeq($tenantId);
        $end = $seq + $target - 1;

        $this->info("Inserindo {$target} pedidos de venda (seq {$seq}…{$end})…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $statuses = array_keys(\App\Models\SaleOrder::STATUSES);
        $clientCount = count($clientIds);
        $productCount = count($productIds);
        $created = 0;
        $remaining = $target;

        while ($remaining > 0) {
            $batchSize = min($chunk, $remaining);
            $orderRows = [];
            $itemPlans = [];

            for ($i = 0; $i < $batchSize; $i++, $seq++, $remaining--) {
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
                if (! $orderId) {
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

    /**
     * @param  list<int>  $supplierIds
     * @param  list<int>  $productIds
     */
    private function seedPurchaseOrders(
        int $tenantId,
        int $target,
        int $chunk,
        array $supplierIds,
        array $productIds,
        int $userId
    ): int {
        if ($target <= 0) {
            return 0;
        }

        if ($supplierIds === [] || $productIds === []) {
            $this->error('Não há fornecedores/produtos suficientes para criar pedidos de compra.');

            return 0;
        }

        $seq = $this->nextPurchaseOrderSeq($tenantId);
        $end = $seq + $target - 1;

        $this->info("Inserindo {$target} pedidos de compra (seq {$seq}…{$end})…");
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $statuses = array_keys(\App\Models\PurchaseOrder::STATUSES);
        $supplierCount = count($supplierIds);
        $productCount = count($productIds);
        $created = 0;
        $remaining = $target;

        while ($remaining > 0) {
            $batchSize = min($chunk, $remaining);
            $orderRows = [];
            $itemPlans = [];

            for ($i = 0; $i < $batchSize; $i++, $seq++, $remaining--) {
                $uuid = (string) Str::uuid();
                $supplierId = $supplierIds[($seq - 1) % $supplierCount];
                $status = $statuses[($seq - 1) % count($statuses)];
                $itemCount = 1 + (($seq - 1) % 3);
                $subtotal = 0.0;
                $items = [];

                for ($j = 0; $j < $itemCount; $j++) {
                    $productId = $productIds[(($seq - 1) * 3 + $j) % $productCount];
                    $qty = 5 + (($seq + $j) % 20);
                    $unitCost = round(3 + (($seq + $j) % 40) + 0.25, 4);
                    $line = round($qty * $unitCost, 2);
                    $subtotal += $line;
                    $items[] = [
                        'product_id' => $productId,
                        'quantity_ordered' => $qty,
                        'quantity_received' => $status === 'recebido' ? $qty : 0,
                        'unit_cost' => $unitCost,
                        'subtotal' => $line,
                        'batch_number' => sprintf('LOT-SEED-%d-%06d-%d', $tenantId, $seq, $j + 1),
                        'expiry_date' => now()->addMonths(12 + ($j % 12))->toDateString(),
                    ];
                }

                $createdAt = now()->subDays(($seq - 1) % 180)->subMinutes($seq % 1440);
                $freight = ($seq % 7 === 0) ? 25.00 : 0;

                $orderRows[] = $this->onlyExistingColumns('purchase_orders', [
                    'tenant_id' => $tenantId,
                    'uuid' => $uuid,
                    'identify' => sprintf('PC-SEED-%d-%06d', $tenantId, $seq),
                    'supplier_id' => $supplierId,
                    'approved_by' => in_array($status, ['confirmado', 'recebido'], true) ? $userId : null,
                    'status' => $status,
                    'expected_delivery' => $createdAt->copy()->addDays(7 + ($seq % 14))->toDateString(),
                    'received_at' => $status === 'recebido'
                        ? $createdAt->copy()->addDays(5)->toDateTimeString()
                        : null,
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'freight_amount' => $freight,
                    'total' => round($subtotal + $freight, 2),
                    'payment_term_days' => [15, 30, 45, 60][$seq % 4],
                    'payment_method' => ['boleto', 'pix', 'transferencia'][$seq % 3],
                    'notes' => self::MARKER,
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                ]);
                $itemPlans[$uuid] = $items;
            }

            DB::table('purchase_orders')->insert($orderRows);

            $uuids = array_column($orderRows, 'uuid');
            $orderIdByUuid = DB::table('purchase_orders')
                ->where('tenant_id', $tenantId)
                ->whereIn('uuid', $uuids)
                ->pluck('id', 'uuid');

            $itemRows = [];
            foreach ($itemPlans as $uuid => $items) {
                $orderId = $orderIdByUuid[$uuid] ?? null;
                if (! $orderId) {
                    continue;
                }
                foreach ($items as $item) {
                    $itemRows[] = [
                        'purchase_order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'quantity_received' => $item['quantity_received'],
                        'unit_cost' => $item['unit_cost'],
                        'subtotal' => $item['subtotal'],
                        'batch_number' => $item['batch_number'],
                        'expiry_date' => $item['expiry_date'],
                    ];
                }
            }

            foreach (array_chunk($itemRows, $chunk) as $itemChunk) {
                DB::table('purchase_order_items')->insert($itemChunk);
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
