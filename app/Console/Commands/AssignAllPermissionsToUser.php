<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignAllPermissionsToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-all-permissions {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atribuir todas as permissões do tenant a um usuário específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Buscando usuário: {$email}");
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário não encontrado: {$email}");
            return 1;
        }
        
        $this->info("✅ Usuário encontrado: {$user->name}");
        $this->info("   Tenant ID: {$user->tenant_id}");
        
        // Buscar todas as permissões do tenant
        $permissions = Permission::where('tenant_id', $user->tenant_id)->get();
        
        if ($permissions->isEmpty()) {
            $this->error("Nenhuma permissão encontrada para o tenant {$user->tenant_id}");
            return 1;
        }
        
        $this->info("📋 Encontradas {$permissions->count()} permissões");
        
        // Remover permissões existentes
        DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->delete();
        
        $this->info("🗑️  Permissões anteriores removidas");
        
        // Atribuir todas as permissões
        $permissionIds = $permissions->pluck('id')->toArray();
        $user->permissions()->attach($permissionIds);
        
        $this->info("✅ {$permissions->count()} permissões atribuídas ao usuário");
        
        // Verificar permissões do módulo users
        $userPermissions = $permissions->filter(function ($permission) {
            return str_starts_with($permission->slug, 'users.');
        });
        
        $this->newLine();
        $this->info("Permissões do módulo 'users' atribuídas:");
        
        $tableData = [];
        foreach ($userPermissions as $permission) {
            $tableData[] = [
                $permission->slug,
                $permission->name,
            ];
        }
        
        if (!empty($tableData)) {
            $this->table(['Slug', 'Nome'], $tableData);
        } else {
            $this->warn("Nenhuma permissão do módulo 'users' encontrada!");
        }
        
        $this->newLine();
        $this->info("🎉 Processo concluído com sucesso!");
        $this->info("Total de permissões atribuídas: {$user->permissions()->count()}");
        
        return 0;
    }
}
