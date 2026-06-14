<?php

/**
 * Script de teste para verificar a criação do usuário de teste
 * 
 * Uso: php test-user-creation.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

// Simular ambiente Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testando criação do usuário de teste...\n\n";

try {
    // 1. Verificar se o usuário já existe
    $existingUser = User::where('email', 'teste@example.com')->first();
    
    if ($existingUser) {
        echo "✅ Usuário já existe: {$existingUser->name}\n";
        echo "📧 Email: {$existingUser->email}\n";
        echo "🏢 Tenant ID: {$existingUser->tenant_id}\n";
        
        // Verificar permissões
        $permissions = $existingUser->permissions()->count();
        $roles = $existingUser->roles()->count();
        
        echo "🔐 Permissões diretas: {$permissions}\n";
        echo "🎭 Roles: {$roles}\n";
        
        // Verificar permissões via roles
        $rolePermissions = 0;
        foreach ($existingUser->roles as $role) {
            $rolePermissions += $role->permissions()->count();
        }
        echo "🔐 Permissões via roles: {$rolePermissions}\n";
        
    } else {
        echo "❌ Usuário não encontrado. Execute o seeder primeiro:\n";
        echo "   php artisan db:seed --class=TestUserSeeder\n";
        echo "   ou\n";
        echo "   php artisan user:create-test\n";
    }
    
    // 2. Verificar tenant
    $tenant = Tenant::first();
    if ($tenant) {
        echo "\n🏢 Tenant: {$tenant->name}\n";
        echo "🌐 Domain: {$tenant->domain}\n";
        echo "✅ Status: " . ($tenant->is_active ? 'Ativo' : 'Inativo') . "\n";
    }
    
    // 3. Verificar permissões do sistema
    $totalPermissions = Permission::count();
    echo "\n📋 Total de permissões no sistema: {$totalPermissions}\n";
    
    // 4. Verificar roles
    $totalRoles = Role::count();
    echo "🎭 Total de roles no sistema: {$totalRoles}\n";
    
    // 5. Verificar role Super Admin
    $superAdminRole = Role::where('slug', 'super-admin')->first();
    if ($superAdminRole) {
        echo "👑 Super Admin Role encontrada: {$superAdminRole->name}\n";
        echo "🔐 Permissões do Super Admin: " . $superAdminRole->permissions()->count() . "\n";
    }
    
    echo "\n✅ Teste concluído com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "📁 Arquivo: " . $e->getFile() . "\n";
    echo "📍 Linha: " . $e->getLine() . "\n";
}
