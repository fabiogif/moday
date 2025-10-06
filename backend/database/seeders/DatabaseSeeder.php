<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Tymon\JWTAuth\Payload;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Seeders básicos do sistema
            PlansTableSeeder::class,
            TenantsTableSeeder::class,
            
            // Seeders de perfis e permissões
            ProfileSeeder::class,
            PermissionSeeder::class,
            AssignAllPermissionsToProfileSeeder::class,
            
            // Seeders de usuários
            UsersTableSeeder::class,
            
            // Seeders de roles (deprecated)
            RoleSeeder::class,
            RolePermissionSeeder::class,
            
            // Seeders de categorias e produtos
            CategorySeeder::class,
            ProductSeeder::class,
            CategoryProductTableSeeder::class,
            
            // Seeders de clientes e mesas
            ClientSeeder::class,
            TableSeeder::class,
            
            // Seeder do usuário de teste
            TestUserSeeder::class,
            
            PaymentMethodSeeder::class,
        ]);
    }
}
