<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimpleTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Criando usuário de teste simples...');

        // Buscar tenant existente
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('❌ Nenhum tenant encontrado. Execute os seeders básicos primeiro.');
            return;
        }

        $this->command->info("✅ Usando tenant: {$tenant->name}");

        // Buscar role de Super Admin existente
        $superAdminRole = Role::where('slug', 'super-admin')
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$superAdminRole) {
            $this->command->error('❌ Role Super Admin não encontrada. Execute RoleSeeder primeiro.');
            return;
        }

        $this->command->info("✅ Usando role: {$superAdminRole->name}");

        // Buscar todas as permissões existentes
        $allPermissions = Permission::where('tenant_id', $tenant->id)->get();
        $this->command->info("📋 Encontradas {$allPermissions->count()} permissões");

        // Criar ou atualizar usuário de teste
        $testUser = User::updateOrCreate(
            ['email' => 'teste@example.com'],
            [
                'name' => 'Teste',
                'email' => 'teste@example.com',
                'password' => Hash::make('$Duda0793'),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Usuário: {$testUser->name} ({$testUser->email})");

        // Atribuir role de Super Admin ao usuário
        if (!$testUser->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $testUser->roles()->attach($superAdminRole->id);
            $this->command->info("✅ Role Super Admin atribuída ao usuário");
        } else {
            $this->command->info("✅ Role Super Admin já estava atribuída");
        }

        // Atribuir todas as permissões diretamente ao usuário
        $permissionIds = $allPermissions->pluck('id')->toArray();
        
        // Remover permissões existentes do usuário
        $testUser->permissions()->detach();
        
        // Adicionar todas as permissões
        $testUser->permissions()->attach($permissionIds);
        $this->command->info("✅ {$allPermissions->count()} permissões atribuídas diretamente ao usuário");

        // Atribuir todas as permissões ao role de Super Admin
        $superAdminRole->permissions()->sync($permissionIds);
        $this->command->info("✅ {$allPermissions->count()} permissões atribuídas ao role Super Admin");

        $this->command->newLine();
        $this->command->info('🎉 Usuário de teste criado com sucesso!');
        $this->command->table(
            ['Campo', 'Valor'],
            [
                ['Nome', $testUser->name],
                ['Email', $testUser->email],
                ['Senha', '$Duda0793'],
                ['Tenant', $tenant->name],
                ['Role', $superAdminRole->name],
                ['Permissões Diretas', $testUser->permissions()->count()],
                ['Permissões via Role', $superAdminRole->permissions()->count()],
            ]
        );
    }
}
