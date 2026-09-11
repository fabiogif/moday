<?php

namespace Database\Seeders;

use App\Models\{Tenant, User, Profile};
use Illuminate\Database\Seeder;

class FabioUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        $user = User::firstOrCreate(
            ['email' => 'fabio@albatec.com.br', 'tenant_id' => $tenant->id],
            [
                'name' => 'Fabio',
                'password' => bcrypt('123456'),
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Usuário " . ($user->wasRecentlyCreated ? 'criado' : 'já existe') . ": {$user->name} ({$user->email})");

        $superAdminProfile = Profile::where('name', 'Super Admin')
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($superAdminProfile) {
            if (!$user->profiles()->where('profile_id', $superAdminProfile->id)->exists()) {
                $user->profiles()->attach($superAdminProfile->id);
                $this->command->info("✅ Perfil 'Super Admin' vinculado ao usuário {$user->name}");
            } else {
                $this->command->info("ℹ️ Usuário {$user->name} já possui o perfil 'Super Admin'");
            }
        } else {
            $this->command->warn("⚠️ Perfil 'Super Admin' não encontrado. Execute ProfileSeeder primeiro.");
        }
    }
}
