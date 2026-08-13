<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('feature_definitions')->where('key', 'sales_goals')->exists();

        if (!$exists) {
            DB::table('feature_definitions')->insert([
                'key' => 'sales_goals',
                'name' => 'Metas de Venda e Gamificação',
                'description' => 'Metas por vendedor/equipe/região/produto, ranking de vendedores, badges e pontos',
                'category' => 'Comercial',
                'plan_tier' => 'professional',
                'display_order' => 70,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed default achievement definitions (global templates)
        $achievements = [
            [
                'key' => 'first_sale',
                'name' => 'Primeira Venda',
                'description' => 'Realizou a primeira venda no sistema',
                'icon' => 'star',
                'badge_color' => '#10B981',
                'category' => 'sales',
                'trigger_type' => 'order_count',
                'trigger_config' => json_encode(['threshold' => 1]),
                'points_reward' => 10,
                'display_order' => 1,
            ],
            [
                'key' => 'revenue_10k_month',
                'name' => 'R$10k no Mês',
                'description' => 'Atingiu R$10.000 em vendas em um único mês',
                'icon' => 'trending-up',
                'badge_color' => '#3B82F6',
                'category' => 'sales',
                'trigger_type' => 'revenue_threshold',
                'trigger_config' => json_encode(['threshold' => 10000, 'period' => 'month']),
                'points_reward' => 25,
                'display_order' => 2,
            ],
            [
                'key' => 'revenue_50k_month',
                'name' => 'R$50k no Mês',
                'description' => 'Atingiu R$50.000 em vendas em um único mês',
                'icon' => 'zap',
                'badge_color' => '#8B5CF6',
                'category' => 'sales',
                'trigger_type' => 'revenue_threshold',
                'trigger_config' => json_encode(['threshold' => 50000, 'period' => 'month']),
                'points_reward' => 50,
                'display_order' => 3,
            ],
            [
                'key' => 'goal_100',
                'name' => 'Meta Batida',
                'description' => 'Completou 100% de uma meta de vendas',
                'icon' => 'target',
                'badge_color' => '#F59E0B',
                'category' => 'goals',
                'trigger_type' => 'goal_completion',
                'trigger_config' => json_encode(['threshold' => 100]),
                'points_reward' => 50,
                'display_order' => 4,
            ],
            [
                'key' => 'top3_ranking',
                'name' => 'Top 3 Ranking',
                'description' => 'Ficou entre os 3 primeiros no ranking mensal',
                'icon' => 'award',
                'badge_color' => '#EF4444',
                'category' => 'ranking',
                'trigger_type' => 'ranking_position',
                'trigger_config' => json_encode(['position' => 3]),
                'points_reward' => 30,
                'display_order' => 5,
            ],
            [
                'key' => 'streak_7',
                'name' => 'Sequência de 7 Dias',
                'description' => 'Realizou vendas por 7 dias consecutivos',
                'icon' => 'flame',
                'badge_color' => '#F97316',
                'category' => 'streak',
                'trigger_type' => 'streak_days',
                'trigger_config' => json_encode(['days' => 7]),
                'points_reward' => 20,
                'display_order' => 6,
            ],
            [
                'key' => 'streak_30',
                'name' => 'Sequência de 30 Dias',
                'description' => 'Realizou vendas por 30 dias consecutivos',
                'icon' => 'flame',
                'badge_color' => '#DC2626',
                'category' => 'streak',
                'trigger_type' => 'streak_days',
                'trigger_config' => json_encode(['days' => 30]),
                'points_reward' => 100,
                'display_order' => 7,
            ],
        ];

        foreach ($achievements as $achievement) {
            $exists = DB::table('achievement_definitions')
                ->whereNull('tenant_id')
                ->where('key', $achievement['key'])
                ->exists();

            if (!$exists) {
                DB::table('achievement_definitions')->insert(array_merge($achievement, [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('achievement_definitions')->whereNull('tenant_id')->delete();
        DB::table('feature_definitions')->where('key', 'sales_goals')->delete();
    }
};
