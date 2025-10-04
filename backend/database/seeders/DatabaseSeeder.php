<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Seeders básicos do sistema
            PlansTableSeeder::class,
            TenantsTableSeeder::class,
            UsersTableSeeder::class,
            
            // Seeders de roles e permissions
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            
            // Seeders de categorias e produtos
            CategorySeeder::class,
            ProductSeeder::class,
            CategoryProductTableSeeder::class,
            
            // Seeders de clientes e mesas
            ClientSeeder::class,
            TableSeeder::class,
        ]);
    }
}
