<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-test 
                            {--email=teste@example.com : Email do usuário de teste}
                            {--password= : Senha do usuário de teste (opcional — usa TEST_USER_PASSWORD ou aleatória)}
                            {--name=Teste : Nome do usuário de teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove e recria o usuário de teste com todas as permissões';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password') ?: env('TEST_USER_PASSWORD', Str::random(16));
        $name = $this->option('name');

        $this->info('🔄 Resetando usuário de teste...');

        // Buscar tenant
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->error('❌ Nenhum tenant encontrado. Execute os seeders básicos primeiro.');
            return Command::FAILURE;
        }

        // Buscar role de Super Admin
        $superAdminRole = Role::where('slug', 'super-admin')
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$superAdminRole) {
            $this->error('❌ Role Super Admin não encontrada. Execute RoleSeeder primeiro.');
            return Command::FAILURE;
        }

        // Buscar todas as permissões
        $allPermissions = Permission::where('tenant_id', $tenant->id)->get();
        if ($allPermissions->isEmpty()) {
            $this->error('❌ Nenhuma permissão encontrada. Execute PermissionSeeder primeiro.');
            return Command::FAILURE;
        }

        // Remover usuário existente se existir
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $existingUser->permissions()->detach();
            $existingUser->roles()->detach();
            $existingUser->delete();
            $this->info("🗑️ Usuário existente removido: {$email}");
        }

        // Criar novo usuário
        $testUser = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->info("✅ Usuário criado: {$testUser->name}");

        // Atribuir role de Super Admin
        $testUser->roles()->attach($superAdminRole->id);
        $this->info("✅ Role Super Admin atribuída");

        // Atribuir todas as permissões diretamente
        $permissionIds = $allPermissions->pluck('id')->toArray();
        $testUser->permissions()->attach($permissionIds);
        $this->info("✅ {$allPermissions->count()} permissões atribuídas diretamente");

        // Atribuir todas as permissões ao role
        $superAdminRole->permissions()->sync($permissionIds);
        $this->info("✅ {$allPermissions->count()} permissões atribuídas ao role");

        // Exibir resumo
        $this->newLine();
        $this->info('🎉 Usuário de teste resetado com sucesso!');
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

        return Command::SUCCESS;
    }
}
