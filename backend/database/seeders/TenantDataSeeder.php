<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Models\TenantAccessLog;
use App\Models\TenantMetrics;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantDataSeeder extends Seeder
{
    /**
     * Popula dados de teste para o admin panel
     */
    public function run(): void
    {
        $this->command->info('🌱 Populando dados de teste...');

        // Buscar tenants existentes
        $tenants = Tenant::limit(10)->get();

        if ($tenants->isEmpty()) {
            $this->command->warn('⚠️  Nenhum tenant encontrado. Crie alguns tenants primeiro.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->command->info("📦 Populando dados para: {$tenant->name}");

            // Criar usuários para o tenant
            $users = $this->createUsers($tenant);

            // Criar histórico de faturamento
            $this->createBillingHistory($tenant);

            // Criar logs de acesso
            $this->createAccessLogs($tenant, $users);

            // Criar métricas
            $this->createMetrics($tenant);
        }

        $this->command->info('✅ Dados de teste populados com sucesso!');
    }

    private function createUsers(Tenant $tenant): \Illuminate\Support\Collection
    {
        $this->command->info("  👥 Criando usuários...");

        $users = collect();

        // Verificar se já existem usuários
        $existingUsers = DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->limit(3)
            ->get();

        if ($existingUsers->isNotEmpty()) {
            return $existingUsers;
        }

        // Criar 3 usuários de exemplo
        for ($i = 1; $i <= 3; $i++) {
            $user = DB::table('users')->insertGetId([
                'tenant_id' => $tenant->id,
                'name' => "Usuário {$i} - {$tenant->name}",
                'email' => "usuario{$i}@{$tenant->subdomain}.test",
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'created_at' => now()->subDays(rand(30, 90)),
                'updated_at' => now(),
            ]);

            $users->push((object)['id' => $user, 'name' => "Usuário {$i}"]);
        }

        return $users;
    }

    private function createBillingHistory(Tenant $tenant): void
    {
        $this->command->info("  💰 Criando histórico de faturamento...");

        // Verificar se já existem faturas
        $existingBilling = TenantBilling::where('tenant_id', $tenant->id)->count();
        if ($existingBilling > 0) {
            $this->command->info("    ℹ️  Já existem {$existingBilling} faturas");
            return;
        }

        // Criar faturas dos últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $billingDate = now()->subMonths($i)->startOfMonth();
            $dueDate = $billingDate->copy()->addDays(7);
            $paidAt = rand(0, 100) > 10 ? $billingDate->copy()->addDays(rand(1, 5)) : null;

            $status = 'pending';
            if ($paidAt) {
                $status = 'paid';
            } elseif ($dueDate->isPast()) {
                $status = 'overdue';
            }

            TenantBilling::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => 'INV-' . str_pad($tenant->id * 1000 + $i, 6, '0', STR_PAD_LEFT),
                'amount' => $this->getPlanAmount($tenant->subscription_plan),
                'status' => $status,
                'billing_date' => $billingDate,
                'due_date' => $dueDate,
                'paid_at' => $paidAt,
            ]);
        }

        $this->command->info("    ✓ 6 faturas criadas");
    }

    private function createAccessLogs(Tenant $tenant, $users): void
    {
        $this->command->info("  🔐 Criando logs de acesso...");

        // Verificar se já existem logs
        $existingLogs = TenantAccessLog::where('tenant_id', $tenant->id)->count();
        if ($existingLogs > 0) {
            $this->command->info("    ℹ️  Já existem {$existingLogs} logs");
            return;
        }

        // Criar 20 logins dos últimos 30 dias
        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            $loggedInAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));
            $sessionDuration = rand(5, 180); // 5 a 180 minutos
            $loggedOutAt = $loggedInAt->copy()->addMinutes($sessionDuration);

            TenantAccessLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => is_object($user) ? $user->id : $user['id'],
                'logged_in_at' => $loggedInAt,
                'logged_out_at' => rand(0, 100) > 20 ? $loggedOutAt : null,
                'session_duration' => rand(0, 100) > 20 ? $sessionDuration : null,
                'ip_address' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
        }

        $this->command->info("    ✓ 20 logs criados");
    }

    private function createMetrics(Tenant $tenant): void
    {
        $this->command->info("  📊 Criando métricas...");

        // Verificar se já existem métricas
        $existingMetrics = TenantMetrics::where('tenant_id', $tenant->id)->count();
        if ($existingMetrics > 0) {
            $this->command->info("    ℹ️  Já existem {$existingMetrics} métricas");
            return;
        }

        // Criar métricas dos últimos 30 dias
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);

            TenantMetrics::create([
                'tenant_id' => $tenant->id,
                'metric_date' => $date->format('Y-m-d'),
                'active_users' => rand(5, 20),
                'total_users' => rand(20, 50),
                'total_orders' => rand(10, 100),
                'total_revenue' => rand(500, 5000),
                'messages_sent' => rand(50, 500),
                'login_count' => rand(10, 50),
                'last_activity_at' => $date,
            ]);
        }

        $this->command->info("    ✓ 30 dias de métricas criadas");
    }

    private function getPlanAmount(string $plan): float
    {
        return match ($plan) {
            'starter' => 49.00,
            'basic' => 99.00,
            'pro' => 149.00,
            'enterprise' => 299.00,
            default => 99.00,
        };
    }
}

