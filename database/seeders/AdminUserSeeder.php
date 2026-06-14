<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cria Super Admin padrão
        AdminUser::firstOrCreate(
            ['email' => 'admin@moday.app'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'), // MUDAR EM PRODUÇÃO!
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Super Admin criado com sucesso!');
        $this->command->info('📧 Email: admin@moday.app');
        $this->command->info('🔑 Senha: admin123');
        $this->command->warn('⚠️  IMPORTANTE: Altere a senha em produção!');
    }
}

