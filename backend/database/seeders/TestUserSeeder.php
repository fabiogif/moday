<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = env('TEST_USER_PASSWORD') ?: Str::random(12);

        // Buscar ou criar tenant padrão
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Restaurante Teste',
                'domain' => 'teste.local',
                'is_active' => true,
            ]);
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

        // Buscar todas as permissões
        $allPermissions = Permission::where('tenant_id', $tenant->id)->get();

        // Criar ou atualizar usuário de teste
        $testUser = User::updateOrCreate(
            ['email' => 'teste@example.com'],
            [
                'name' => 'Teste',
                'email' => 'teste@example.com',
                'password' => Hash::make($password),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]
        );

        // Atribuir role de Super Admin ao usuário
        if (!$testUser->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $testUser->roles()->attach($superAdminRole->id);
        }

        // Atribuir todas as permissões diretamente ao usuário
        $permissionIds = $allPermissions->pluck('id')->toArray();
        
        // Remover permissões existentes do usuário
        $testUser->permissions()->detach();
        
        // Adicionar todas as permissões
        $testUser->permissions()->attach($permissionIds);

        // Atribuir todas as permissões ao role de Super Admin
        $superAdminRole->permissions()->sync($permissionIds);

        $this->command->info("✅ Usuário de teste criado com sucesso!");
        $this->command->info("📧 Email: teste@example.com");
        $this->command->info("🔑 Senha: {$password}");
        $this->command->info("👤 Nome: Teste");
        $this->command->info("🏢 Tenant: {$tenant->name}");
        $this->command->info("🎭 Role: {$superAdminRole->name}");
        $this->command->info("🔐 Permissões: {$allPermissions->count()} permissões atribuídas");
    }
}
