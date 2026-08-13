-- ============================================================================
-- SCRIPT SQL PARA IMPLEMENTAÇÃO DO SISTEMA DE REGISTRO E PLANOS
-- Data: 13/10/2025
-- Descrição: Cria tabelas e dados necessários para o sistema de registro
-- ============================================================================

-- IMPORTANTE: Execute este script somente se não puder usar php artisan migrate
-- Recomendado: Use sempre php artisan migrate quando possível

-- ============================================================================
-- 1. ADICIONAR SOFT DELETES EM PRODUCTS
-- ============================================================================

ALTER TABLE products 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- ============================================================================
-- 2. CRIAR TABELA DE PLANOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    url VARCHAR(255) NOT NULL UNIQUE,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    max_users INT NOT NULL DEFAULT 1,
    max_products INT NOT NULL DEFAULT 50,
    max_orders_per_month INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_plans_url (url),
    INDEX idx_plans_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. CRIAR TABELA DE DETALHES DOS PLANOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS details_plan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
    INDEX idx_details_plan_plan_id (plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. CRIAR TABELA DE RELACIONAMENTO PLANO-PERFIL
-- ============================================================================

CREATE TABLE IF NOT EXISTS plan_profile (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    profile_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    INDEX idx_plan_profile_plan_id (plan_id),
    INDEX idx_plan_profile_profile_id (profile_id),
    UNIQUE KEY unique_plan_profile (plan_id, profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. POPULAR PLANOS INICIAIS
-- ============================================================================

-- Inserir Plano Básico
INSERT INTO plans (name, url, price, description, is_active, max_users, max_products, max_orders_per_month, created_at, updated_at)
VALUES (
    'Básico',
    'basico',
    49.90,
    'Perfeito para pequenos negócios começando',
    TRUE,
    2,
    100,
    500,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    price = 49.90,
    description = 'Perfeito para pequenos negócios começando',
    is_active = TRUE,
    max_users = 2,
    max_products = 100,
    max_orders_per_month = 500,
    updated_at = NOW();

SET @plan_basico_id = LAST_INSERT_ID();

-- Detalhes do Plano Básico
INSERT INTO details_plan (plan_id, name, created_at, updated_at) VALUES
(@plan_basico_id, 'Até 100 produtos cadastrados', NOW(), NOW()),
(@plan_basico_id, 'Até 2 usuários simultâneos', NOW(), NOW()),
(@plan_basico_id, '500 pedidos por mês', NOW(), NOW()),
(@plan_basico_id, 'Painel administrativo completo', NOW(), NOW()),
(@plan_basico_id, 'Suporte por email', NOW(), NOW()),
(@plan_basico_id, 'Cardápio digital personalizado', NOW(), NOW()),
(@plan_basico_id, 'Relatórios básicos', NOW(), NOW());

-- Inserir Plano Profissional
INSERT INTO plans (name, url, price, description, is_active, max_users, max_products, max_orders_per_month, created_at, updated_at)
VALUES (
    'Profissional',
    'profissional',
    99.90,
    'Para negócios que precisam de mais recursos',
    TRUE,
    10,
    1000,
    5000,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    price = 99.90,
    description = 'Para negócios que precisam de mais recursos',
    is_active = TRUE,
    max_users = 10,
    max_products = 1000,
    max_orders_per_month = 5000,
    updated_at = NOW();

SET @plan_prof_id = LAST_INSERT_ID();

-- Detalhes do Plano Profissional
INSERT INTO details_plan (plan_id, name, created_at, updated_at) VALUES
(@plan_prof_id, 'Até 1.000 produtos cadastrados', NOW(), NOW()),
(@plan_prof_id, 'Até 10 usuários simultâneos', NOW(), NOW()),
(@plan_prof_id, '5.000 pedidos por mês', NOW(), NOW()),
(@plan_prof_id, 'Painel avançado com relatórios', NOW(), NOW()),
(@plan_prof_id, 'Suporte prioritário via WhatsApp', NOW(), NOW()),
(@plan_prof_id, 'Múltiplos usuários e permissões', NOW(), NOW()),
(@plan_prof_id, 'Integrações com delivery', NOW(), NOW()),
(@plan_prof_id, 'Personalização completa da marca', NOW(), NOW()),
(@plan_prof_id, 'Relatórios avançados e exportação', NOW(), NOW()),
(@plan_prof_id, 'Controle de estoque inteligente', NOW(), NOW());

-- Inserir Plano Enterprise
INSERT INTO plans (name, url, price, description, is_active, max_users, max_products, max_orders_per_month, created_at, updated_at)
VALUES (
    'Enterprise',
    'enterprise',
    199.90,
    'Solução completa para grandes operações',
    TRUE,
    999999,
    999999,
    999999,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    price = 199.90,
    description = 'Solução completa para grandes operações',
    is_active = TRUE,
    max_users = 999999,
    max_products = 999999,
    max_orders_per_month = 999999,
    updated_at = NOW();

SET @plan_enterprise_id = LAST_INSERT_ID();

-- Detalhes do Plano Enterprise
INSERT INTO details_plan (plan_id, name, created_at, updated_at) VALUES
(@plan_enterprise_id, 'Produtos ilimitados', NOW(), NOW()),
(@plan_enterprise_id, 'Usuários ilimitados', NOW(), NOW()),
(@plan_enterprise_id, 'Pedidos ilimitados', NOW(), NOW()),
(@plan_enterprise_id, 'Todos os recursos do Profissional', NOW(), NOW()),
(@plan_enterprise_id, 'Suporte 24/7 com SLA garantido', NOW(), NOW()),
(@plan_enterprise_id, 'Gerente de conta dedicado', NOW(), NOW()),
(@plan_enterprise_id, 'API personalizada', NOW(), NOW()),
(@plan_enterprise_id, 'Treinamento completo da equipe', NOW(), NOW()),
(@plan_enterprise_id, 'Múltiplas lojas/filiais', NOW(), NOW()),
(@plan_enterprise_id, 'Integração com ERP', NOW(), NOW()),
(@plan_enterprise_id, 'Customizações sob demanda', NOW(), NOW());

-- ============================================================================
-- VERIFICAÇÃO
-- ============================================================================

-- Verificar quantos planos foram criados
SELECT COUNT(*) as total_planos FROM plans;

-- Verificar quantos detalhes foram criados
SELECT COUNT(*) as total_detalhes FROM details_plan;

-- Listar planos com contagem de detalhes
SELECT 
    p.id,
    p.name,
    p.price,
    COUNT(d.id) as total_detalhes
FROM plans p
LEFT JOIN details_plan d ON p.id = d.plan_id
GROUP BY p.id, p.name, p.price
ORDER BY p.price ASC;

-- ============================================================================
-- FIM DO SCRIPT
-- ============================================================================
