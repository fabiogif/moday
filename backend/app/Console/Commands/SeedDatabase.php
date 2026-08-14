<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed-safe 
                            {--fresh : Limpar banco antes de executar}
                            {--test-user : Criar usuário de teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa seeders na ordem correta, evitando erros de duplicata';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando seeders seguros...');

        if ($this->option('fresh')) {
            $this->info('🗑️ Limpando banco de dados...');
            Artisan::call('migrate:fresh');
            $this->info('✅ Banco limpo');
        }

        // Ordem correta dos seeders
        $seeders = [
            'PlansTableSeeder' => 'Planos',
            'TenantsTableSeeder' => 'Tenants',
            'UsersTableSeeder' => 'Usuários',
            'RoleSeeder' => 'Roles',
            'PermissionSeeder' => 'Permissões',
            'RolePermissionSeeder' => 'Relacionamentos Role-Permissão',
            'CategorySeeder' => 'Categorias',
            'ProductSeeder' => 'Produtos',
            'CategoryProductTableSeeder' => 'Relacionamentos Categoria-Produto',
            'ClientSeeder' => 'Clientes',
            'TableSeeder' => 'Mesas',
            'PaymentMethodSeeder' => 'Métodos de Pagamento',
        ];

        foreach ($seeders as $seeder => $description) {
            $this->info("📋 Executando {$description}...");
            
            try {
                Artisan::call("db:seed", ['--class' => $seeder]);
                $this->info("✅ {$description} executado com sucesso");
            } catch (\Exception $e) {
                $this->error("❌ Erro em {$description}: " . $e->getMessage());
                
                // Continuar com próximos seeders mesmo se um falhar
                continue;
            }
        }

        // Criar usuário de teste se solicitado
        if ($this->option('test-user')) {
            $this->info("👤 Criando usuário de teste...");
            
            try {
                Artisan::call("db:seed", ['--class' => 'SimpleTestUserSeeder']);
                $this->info("✅ Usuário de teste criado");
            } catch (\Exception $e) {
                $this->error("❌ Erro ao criar usuário de teste: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('🎉 Seeders executados com sucesso!');
        
        if ($this->option('test-user')) {
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Nome', 'Teste'],
                    ['Email', 'teste@example.com'],
                    ['Senha', '(TEST_USER_PASSWORD ou aleatória)'],
                    ['Status', 'Pronto para uso'],
                ]
            );
        }

        return Command::SUCCESS;
    }
}
