<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Factory para criar usuário de teste com todas as permissões
 */
class TestUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => Hash::make('$Duda0793'),
            'email_verified_at' => now(),
            'is_active' => true,
            'tenant_id' => function () {
                return Tenant::firstOrCreate(
                    ['domain' => 'teste.local'],
                    [
                        'name' => 'Restaurante Teste',
                        'is_active' => true,
                    ]
                )->id;
            },
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            // Buscar ou criar role de Super Admin
            $superAdminRole = Role::firstOrCreate(
                ['slug' => 'super-admin'],
                [
                    'name' => 'Super Admin',
                    'slug' => 'super-admin',
                    'description' => 'Acesso total ao sistema',
                    'level' => 1,
                    'is_active' => true,
                    'tenant_id' => $user->tenant_id,
                ]
            );

            // Buscar todas as permissões do tenant
            $allPermissions = Permission::where('tenant_id', $user->tenant_id)->get();

            // Atribuir role de Super Admin ao usuário
            if (!$user->roles()->where('role_id', $superAdminRole->id)->exists()) {
                $user->roles()->attach($superAdminRole->id);
            }

            // Atribuir todas as permissões diretamente ao usuário
            $permissionIds = $allPermissions->pluck('id')->toArray();
            $user->permissions()->sync($permissionIds);

            // Atribuir todas as permissões ao role de Super Admin
            $superAdminRole->permissions()->sync($permissionIds);
        });
    }

    /**
     * Indicate that the user should have all permissions.
     */
    public function withAllPermissions(): static
    {
        return $this->configure();
    }

    /**
     * Indicate that the user should be a super admin.
     */
    public function superAdmin(): static
    {
        return $this->configure();
    }
}
