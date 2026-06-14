<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureDefinition;
use App\Models\Plan;
use App\Models\PlanFeature;

class FeatureDefinitionsSeeder extends Seeder
{
    private const FEATURES = [
        // Estoque
        ['key' => 'min_safety_stock',   'name' => 'Estoque mínimo e de segurança',             'category' => 'Estoque',    'plan_tier' => 'basic',        'display_order' => 10],
        ['key' => 'expired_lot_block',  'name' => 'Bloqueio automático de lotes vencidos',      'category' => 'Estoque',    'plan_tier' => 'basic',        'display_order' => 20],
        ['key' => 'reorder_suggestions','name' => 'Sugestão de reposição inteligente',           'category' => 'Estoque',    'plan_tier' => 'professional', 'display_order' => 30],
        ['key' => 'barcode_picking',    'name' => 'Picking com QR Code / código de barras',     'category' => 'Estoque',    'plan_tier' => 'enterprise',   'display_order' => 40],
        ['key' => 'wave_picking',       'name' => 'Picking por zona e onda',                    'category' => 'Estoque',    'plan_tier' => 'enterprise',   'display_order' => 50],

        // Comercial
        ['key' => 'discount_ceiling',   'name' => 'Teto de desconto por perfil',                'category' => 'Comercial',  'plan_tier' => 'basic',        'display_order' => 10],
        ['key' => 'pwa_sales_app',      'name' => 'App de vendedores (PWA offline)',             'category' => 'Comercial',  'plan_tier' => 'professional', 'display_order' => 20],
        ['key' => 'client_portal_b2b',  'name' => 'Portal do cliente B2B',                      'category' => 'Comercial',  'plan_tier' => 'enterprise',   'display_order' => 30],

        // Financeiro
        ['key' => 'cash_flow_report',   'name' => 'Fluxo de caixa projetado',                   'category' => 'Financeiro', 'plan_tier' => 'professional', 'display_order' => 10],
        ['key' => 'aging_report',       'name' => 'Painel de inadimplência com aging',           'category' => 'Financeiro', 'plan_tier' => 'professional', 'display_order' => 20],
        ['key' => 'b2b_payment_link',   'name' => 'Link de pagamento Pix/cartão para clientes', 'category' => 'Financeiro', 'plan_tier' => 'professional', 'display_order' => 30],
        ['key' => 'debt_renegotiation', 'name' => 'Renegociação de dívidas em atraso',          'category' => 'Financeiro', 'plan_tier' => 'enterprise',   'display_order' => 40],

        // Fiscal
        ['key' => 'nfe_correction',     'name' => 'Carta de correção e inutilização de NF-e',  'category' => 'Fiscal',     'plan_tier' => 'professional', 'display_order' => 10],

        // Analytics
        ['key' => 'abc_clients',        'name' => 'Curva ABC de clientes',                      'category' => 'Analytics',  'plan_tier' => 'professional', 'display_order' => 10],
        ['key' => 'abc_suppliers',      'name' => 'Curva ABC de fornecedores',                  'category' => 'Analytics',  'plan_tier' => 'professional', 'display_order' => 20],
        ['key' => 'executive_dashboard','name' => 'Dashboard executivo com margem e por vendedor','category' => 'Analytics', 'plan_tier' => 'professional', 'display_order' => 30],
        ['key' => 'ai_demand_forecast', 'name' => 'IA para previsão de demanda',                'category' => 'Analytics',  'plan_tier' => 'enterprise',   'display_order' => 40],
        ['key' => 'delinquency_score',  'name' => 'Análise preditiva de inadimplência',         'category' => 'Analytics',  'plan_tier' => 'enterprise',   'display_order' => 50],

        // Logística
        ['key' => 'smart_routing',      'name' => 'Roteirização inteligente com GPS',           'category' => 'Logística',  'plan_tier' => 'enterprise',   'display_order' => 10],
        ['key' => 'delivery_routing',   'name' => 'Roteirização e custo por entrega',           'category' => 'Logística',  'plan_tier' => 'professional', 'display_order' => 20],

        // Sazonalidade e Alertas
        ['key' => 'seasonal_alerts',    'name' => 'Alertas automáticos e datas comemorativas',  'category' => 'Sazonalidade', 'plan_tier' => 'professional', 'display_order' => 10],
        ['key' => 'seasonal_trends',    'name' => 'Tendências de consumo e sazonalidade',       'category' => 'Sazonalidade', 'plan_tier' => 'professional', 'display_order' => 20],

        // Antifraude
        ['key' => 'price_history',      'name' => 'Histórico de alterações de preço',           'category' => 'Antifraude', 'plan_tier' => 'basic',        'display_order' => 10],
        ['key' => 'discount_approvals', 'name' => 'Aprovação de descontos por nível',           'category' => 'Antifraude', 'plan_tier' => 'professional', 'display_order' => 20],
        ['key' => 'audit_logs',         'name' => 'Registro de todas as alterações (auditoria)','category' => 'Antifraude', 'plan_tier' => 'basic',        'display_order' => 30],
    ];

    private const PLAN_TIER_FEATURES = [
        'basic'        => ['min_safety_stock', 'expired_lot_block', 'discount_ceiling', 'price_history', 'audit_logs'],
        'professional' => [
            'min_safety_stock', 'expired_lot_block', 'discount_ceiling',
            'reorder_suggestions', 'pwa_sales_app',
            'cash_flow_report', 'aging_report', 'b2b_payment_link',
            'nfe_correction',
            'abc_clients', 'abc_suppliers', 'executive_dashboard',
            'delivery_routing',
            'seasonal_alerts', 'seasonal_trends', 'discount_approvals',
        ],
        'enterprise'   => null, // null means ALL features
    ];

    public function run(): void
    {
        // Upsert feature definitions
        foreach (self::FEATURES as $feature) {
            FeatureDefinition::updateOrCreate(
                ['key' => $feature['key']],
                array_merge($feature, ['is_active' => true])
            );
        }

        // Seed plan_features for existing plans
        $plans = Plan::all();
        $allKeys = array_column(self::FEATURES, 'key');

        foreach ($plans as $plan) {
            $tier = $this->detectTier($plan);
            $enabledKeys = $tier === 'enterprise'
                ? $allKeys
                : (self::PLAN_TIER_FEATURES[$tier] ?? []);

            foreach ($allKeys as $key) {
                PlanFeature::updateOrCreate(
                    ['plan_id' => $plan->id, 'feature_key' => $key],
                    ['is_enabled' => in_array($key, $enabledKeys)]
                );
            }
        }
    }

    private function detectTier(Plan $plan): string
    {
        if (str_contains(strtolower($plan->url ?? ''), 'enterprise')) return 'enterprise';
        if (str_contains(strtolower($plan->url ?? ''), 'profissional')) return 'professional';
        if (str_contains(strtolower($plan->url ?? ''), 'professional')) return 'professional';
        // Decide by price as fallback
        if ((float) $plan->price >= 600) return 'enterprise';
        if ((float) $plan->price >= 300) return 'professional';
        return 'basic';
    }
}
