<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignAllPermissionsToProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Atribuindo todas as permissões ao Profile ID 1...');

        // Buscar o profile com ID 1
        $profile = Profile::find(1);
        
        if (!$profile) {
            $this->command->error('❌ Profile com ID 1 não encontrado.');
            return;
        }

        $this->command->info("✅ Profile encontrado: {$profile->name}");

        // Buscar todas as permissões do tenant do profile
        $allPermissions = Permission::where('tenant_id', $profile->tenant_id)->get();
        
        if ($allPermissions->isEmpty()) {
            $this->command->error('❌ Nenhuma permissão encontrada para o tenant.');
            return;
        }

        $this->command->info("📋 Encontradas {$allPermissions->count()} permissões");

        // Remover permissões existentes do profile
        $profile->permissions()->detach();
        $this->command->info("🗑️ Permissões existentes removidas");

        // Atribuir todas as permissões ao profile
        $permissionIds = $allPermissions->pluck('id')->toArray();
        $profile->permissions()->attach($permissionIds);
        
        $this->command->info("✅ {$allPermissions->count()} permissões atribuídas ao profile");

        // Verificar se foi atribuído corretamente
        $assignedPermissions = $profile->permissions()->count();
        $this->command->info("🔍 Permissões atribuídas: {$assignedPermissions}");

        $this->command->newLine();
        $this->command->info('🎉 Todas as permissões foram atribuídas ao Profile ID 1!');
        
        $this->command->table(
            ['Campo', 'Valor'],
            [
                ['Profile ID', $profile->id],
                ['Profile Nome', $profile->name],
                ['Tenant ID', $profile->tenant_id],
                ['Permissões Atribuídas', $assignedPermissions],
                ['Total de Permissões', $allPermissions->count()],
            ]
        );
    }
}
