<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\{Tenant, User, Profile};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        // Criar ou buscar usuário existente
        $user = User::firstOrCreate(
            ['email' => 'fabio@fabio.com', 'tenant_id' => $tenant->id],
            [
                'name' => 'Fabio',
                'password' => bcrypt('123456'),
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Usuário " . ($user->wasRecentlyCreated ? 'criado' : 'já existe') . ": {$user->name} ({$user->email})");

        // Vincular ao perfil Super Admin
        $superAdminProfile = Profile::where('name', 'Super Admin')
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($superAdminProfile) {
            // Verificar se o usuário já tem o perfil
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
