<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\DetailsPlan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plansData = [
            [
                'name' => 'Grátis',
                'url' => 'gratis',
                'price' => 0.00,
                'description' => 'Perfeito para testar o sistema',
                'is_active' => true,
                'max_users' => 1,
                'max_products' => 50,
                'max_orders_per_month' => 30,
                'has_marketing' => false,
                'has_order_completion_email' => false,
                'has_reports' => false,
                'details' => [
                    'Até 50 produtos cadastrados',
                    '1 usuário',
                    '30 pedidos por mês',
                    'Painel administrativo básico',
                    'Cardápio digital',
                    'Sem acesso a Marketing',
                    'Sem acesso a Relatórios',
                ]
            ],
            [
                'name' => 'Básico',
                'url' => 'basico',
                'price' => 49.90,
                'description' => 'Perfeito para pequenos negócios começando',
                'is_active' => true,
                'max_users' => 5,
                'max_products' => 100,
                'max_orders_per_month' => 100,
                'has_marketing' => true,
                'has_order_completion_email' => true,
                'has_reports' => true,
                'details' => [
                    'Até 100 produtos cadastrados',
                    'Até 5 usuários simultâneos',
                    '100 pedidos por mês',
                    'Painel administrativo completo',
                    'Cardápio digital personalizado',
                    'Acesso a Marketing',
                    'Acesso a Relatórios',
                    'Suporte por email',
                ]
            ],
            [
                'name' => 'Premium',
                'url' => 'premium',
                'price' => 99.90,
                'description' => 'Solução completa para grandes operações',
                'is_active' => true,
                'max_users' => 999999,
                'max_products' => 999999,
                'max_orders_per_month' => null, // null = ilimitado
                'has_marketing' => true,
                'has_order_completion_email' => true,
                'has_reports' => true,
                'details' => [
                    'Produtos ilimitados',
                    'Usuários ilimitados',
                    'Pedidos ilimitados',
                    'Acesso a todas funcionalidades',
                    'Acesso a Marketing',
                    'Acesso a Relatórios',
                    'Suporte prioritário via WhatsApp',
                    'Múltiplos usuários e permissões',
                    'Integrações com delivery',
                    'Personalização completa da marca',
                    'Relatórios avançados e exportação',
                    'Controle de estoque inteligente',
                ]
            ],
        ];

        foreach ($plansData as $planData) {
            $details = $planData['details'];
            unset($planData['details']);
            
            // Usar updateOrCreate para evitar duplicatas
            $plan = Plan::updateOrCreate(
                ['url' => $planData['url']], // Condição de busca
                $planData // Dados a atualizar/criar
            );
            
            // Remover detalhes antigos para recriar
            $plan->details()->delete();
            
            // Criar novos detalhes
            foreach ($details as $detailName) {
                DetailsPlan::create([
                    'plan_id' => $plan->id,
                    'name' => $detailName,
                    'description' => $detailName, // Usar o mesmo nome como descrição
                ]);
            }
        }
        
        $this->command->info('✅ Planos criados/atualizados com sucesso!');
        $this->command->info('📦 Total de planos: ' . Plan::count());
        $this->command->info('📋 Total de detalhes: ' . DetailsPlan::count());
    }
}
