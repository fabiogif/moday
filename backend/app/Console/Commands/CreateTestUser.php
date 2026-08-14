<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test 
                            {--email=teste@example.com : Email do usuário de teste}
                            {--password= : Senha do usuário de teste (opcional — usa TEST_USER_PASSWORD ou aleatória)}
                            {--name=Teste : Nome do usuário de teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria um usuário de teste com todas as permissões do sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password') ?: env('TEST_USER_PASSWORD', Str::random(16));
        $name = $this->option('name');

        $this->info('🚀 Criando usuário de teste...');

        // Buscar ou criar tenant padrão
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Restaurante Teste',
                'domain' => 'teste.local',
                'is_active' => true,
            ]);
            $this->info("✅ Tenant criado: {$tenant->name}");
        }

        // Buscar ou criar role de Super Admin
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin', 'tenant_id' => $tenant->id],
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Acesso total ao sistema',
                'level' => 1,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ]
        );
        $this->info("✅ Role Super Admin: " . ($superAdminRole->wasRecentlyCreated ? 'criada' : 'já existe'));

        // Buscar todas as permissões
        $allPermissions = Permission::where('tenant_id', $tenant->id)->get();
        $this->info("📋 Encontradas {$allPermissions->count()} permissões no sistema");

        // Criar ou atualizar usuário de teste
        $testUser = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $this->info("✅ Usuário criado/atualizado: {$testUser->name}");

        // Atribuir role de Super Admin ao usuário
        if (!$testUser->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $testUser->roles()->attach($superAdminRole->id);
            $this->info("✅ Role Super Admin atribuída ao usuário");
        }

        // Atribuir todas as permissões diretamente ao usuário
        $permissionIds = $allPermissions->pluck('id')->toArray();
        
        // Remover permissões existentes do usuário
        $testUser->permissions()->detach();
        
        // Adicionar todas as permissões
        $testUser->permissions()->attach($permissionIds);
        $this->info("✅ {$allPermissions->count()} permissões atribuídas diretamente ao usuário");

        // Atribuir todas as permissões ao role de Super Admin
        $superAdminRole->permissions()->sync($permissionIds);
        $this->info("✅ {$allPermissions->count()} permissões atribuídas ao role Super Admin");

        // Exibir resumo
        $this->newLine();
        $this->info('🎉 Usuário de teste criado com sucesso!');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Nome', $testUser->name],
                ['Email', $testUser->email],
                ['Senha', $password],
                ['Tenant', $tenant->name],
                ['Role', $superAdminRole->name],
                ['Permissões Diretas', $testUser->permissions()->count()],
                ['Permissões via Role', $superAdminRole->permissions()->count()],
            ]
        );

        $this->newLine();
        $this->info('🔐 O usuário possui acesso total ao sistema!');
        $this->info('📧 Use as credenciais acima para fazer login na aplicação.');

        return Command::SUCCESS;
    }
}
