<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\Permission;
use Illuminate\Console\Command;

class AssignAllPermissionsToProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profile:assign-all-permissions 
                            {profile_id=1 : ID do profile para atribuir permissões}
                            {--tenant= : ID do tenant (opcional, usa o tenant do profile)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atribui todas as permissões a um profile específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $profileId = $this->argument('profile_id');
        $tenantId = $this->option('tenant');

        $this->info("🔐 Atribuindo todas as permissões ao Profile ID {$profileId}...");

        // Buscar o profile
        $profile = Profile::find($profileId);
        
        if (!$profile) {
            $this->error("❌ Profile com ID {$profileId} não encontrado.");
            return Command::FAILURE;
        }

        $this->info("✅ Profile encontrado: {$profile->name}");

        // Determinar tenant
        $targetTenantId = $tenantId ?: $profile->tenant_id;
        
        // Buscar todas as permissões do tenant
        $allPermissions = Permission::where('tenant_id', $targetTenantId)->get();
        
        if ($allPermissions->isEmpty()) {
            $this->error("❌ Nenhuma permissão encontrada para o tenant ID {$targetTenantId}.");
            return Command::FAILURE;
        }

        $this->info("📋 Encontradas {$allPermissions->count()} permissões no tenant {$targetTenantId}");

        // Remover permissões existentes do profile
        $existingPermissions = $profile->permissions()->count();
        if ($existingPermissions > 0) {
            $profile->permissions()->detach();
            $this->info("🗑️ {$existingPermissions} permissões existentes removidas");
        }

        // Atribuir todas as permissões ao profile
        $permissionIds = $allPermissions->pluck('id')->toArray();
        $profile->permissions()->attach($permissionIds);
        
        $this->info("✅ {$allPermissions->count()} permissões atribuídas ao profile");

        // Verificar se foi atribuído corretamente
        $assignedPermissions = $profile->permissions()->count();
        $this->info("🔍 Total de permissões no profile: {$assignedPermissions}");

        // Exibir resumo
        $this->newLine();
        $this->info('🎉 Todas as permissões foram atribuídas com sucesso!');
        
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Profile ID', $profile->id],
                ['Profile Nome', $profile->name],
                ['Profile Descrição', $profile->description],
                ['Tenant ID', $profile->tenant_id],
                ['Permissões Anteriores', $existingPermissions],
                ['Permissões Atribuídas', $allPermissions->count()],
                ['Total Final', $assignedPermissions],
            ]
        );

        // Listar algumas permissões como exemplo
        $this->newLine();
        $this->info('📋 Exemplos de permissões atribuídas:');
        $samplePermissions = $profile->permissions()->take(5)->get(['name', 'slug', 'module']);
        foreach ($samplePermissions as $permission) {
            $this->line("  • {$permission->name} ({$permission->slug}) - Módulo: {$permission->module}");
        }
        
        if ($assignedPermissions > 5) {
            $this->line("  ... e mais " . ($assignedPermissions - 5) . " permissões");
        }

        return Command::SUCCESS;
    }
}
