<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateRolesToProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:roles-to-profiles {--dry-run : Execute em modo de teste sem salvar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar Roles para Profiles (simplificação do ACL)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: Nenhuma alteração será salva');
        } else {
            if (!$this->confirm('⚠️  Esta operação irá migrar Roles para Profiles. Você fez backup do banco?')) {
                $this->error('❌ Operação cancelada. Faça backup primeiro!');
                return 1;
            }
        }

        $this->info('🚀 Iniciando migração de Roles para Profiles...');
        $this->newLine();

        try {
            if (!$dryRun) {
                DB::beginTransaction();
            }

            // PASSO 1: Migrar Roles para Profiles
            $this->step1MigrateRolesToProfiles($dryRun);
            
            // PASSO 2: Migrar Permissões
            $this->step2MigratePermissions($dryRun);
            
            // PASSO 3: Migrar Associações de Usuários
            $this->step3MigrateUserAssociations($dryRun);
            
            // PASSO 4: Verificação
            $this->step4Verification();

            if (!$dryRun) {
                DB::commit();
                $this->newLine();
                $this->info('✅ Migração concluída com sucesso!');
            } else {
                $this->newLine();
                $this->warn('🔍 Modo dry-run concluído. Nenhuma alteração foi salva.');
            }

            $this->newLine();
            $this->info('📋 Próximos passos:');
            $this->line('1. Verifique os dados migrados');
            $this->line('2. Teste o sistema completamente');
            $this->line('3. Depois de confirmar que tudo funciona:');
            $this->line('   - Comente as rotas de Roles em routes/api.php');
            $this->line('   - (Opcional) Delete os dados de Roles do banco');

            return 0;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error('❌ Erro na migração: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function step1MigrateRolesToProfiles($dryRun)
    {
        $this->info('📦 PASSO 1: Migrando Roles para Profiles...');
        
        $roles = Role::all();
        $created = 0;
        $skipped = 0;

        foreach ($roles as $role) {
            $existingProfile = Profile::where('slug', $role->slug)
                ->where('tenant_id', $role->tenant_id)
                ->first();

            if ($existingProfile) {
                $this->line("   ⏭️  Profile já existe: {$role->name} (slug: {$role->slug})");
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                Profile::create([
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'tenant_id' => $role->tenant_id,
                    'is_active' => $role->is_active,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ]);
            }

            $this->line("   ✓ Criado Profile: {$role->name} (slug: {$role->slug})");
            $created++;
        }

        $this->info("   📊 Resultado: {$created} criados, {$skipped} ignorados (já existiam)");
        $this->newLine();
    }

    private function step2MigratePermissions($dryRun)
    {
        $this->info('🔐 PASSO 2: Migrando Permissões dos Roles para Profiles...');
        
        $migrated = 0;
        $skipped = 0;

        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $profile = Profile::where('slug', $role->slug)
                ->where('tenant_id', $role->tenant_id)
                ->first();

            if (!$profile) {
                $this->warn("   ⚠️  Profile não encontrado para Role: {$role->name}");
                continue;
            }

            foreach ($role->permissions as $permission) {
                $exists = DB::table('permission_profile')
                    ->where('profile_id', $profile->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    DB::table('permission_profile')->insert([
                        'profile_id' => $profile->id,
                        'permission_id' => $permission->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $migrated++;
            }

            $this->line("   ✓ Migradas permissões do Role: {$role->name} -> Profile: {$profile->name}");
        }

        $this->info("   📊 Resultado: {$migrated} permissões migradas, {$skipped} já existiam");
        $this->newLine();
    }

    private function step3MigrateUserAssociations($dryRun)
    {
        $this->info('👥 PASSO 3: Migrando Associações de Usuários...');
        
        $migrated = 0;
        $skipped = 0;

        $users = User::with('roles')->get();

        foreach ($users as $user) {
            foreach ($user->roles as $role) {
                $profile = Profile::where('slug', $role->slug)
                    ->where('tenant_id', $role->tenant_id)
                    ->first();

                if (!$profile) {
                    $this->warn("   ⚠️  Profile não encontrado para Role: {$role->name}");
                    continue;
                }

                $exists = DB::table('user_profiles')
                    ->where('user_id', $user->id)
                    ->where('profile_id', $profile->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    DB::table('user_profiles')->insert([
                        'user_id' => $user->id,
                        'profile_id' => $profile->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $migrated++;
            }

            if ($user->roles->count() > 0) {
                $this->line("   ✓ Migrado usuário: {$user->name} ({$user->email})");
            }
        }

        $this->info("   📊 Resultado: {$migrated} associações migradas, {$skipped} já existiam");
        $this->newLine();
    }

    private function step4Verification()
    {
        $this->info('🔍 PASSO 4: Verificação dos Dados Migrados');
        
        $profilesCount = Profile::count();
        $userProfilesCount = DB::table('user_profiles')->count();
        $permissionProfilesCount = DB::table('permission_profile')->count();

        $this->table(
            ['Métrica', 'Total'],
            [
                ['Profiles Cadastrados', $profilesCount],
                ['Usuários com Profiles', $userProfilesCount],
                ['Permissões em Profiles', $permissionProfilesCount],
            ]
        );

        // Listar alguns profiles criados
        $profiles = Profile::withCount(['users', 'permissions'])->limit(10)->get();
        
        if ($profiles->count() > 0) {
            $this->newLine();
            $this->info('📋 Amostra de Profiles (primeiros 10):');
            
            $data = [];
            foreach ($profiles as $profile) {
                $data[] = [
                    $profile->name,
                    $profile->slug,
                    $profile->users_count ?? 0,
                    $profile->permissions_count ?? 0,
                ];
            }

            $this->table(
                ['Nome', 'Slug', 'Usuários', 'Permissões'],
                $data
            );
        }
    }
}
