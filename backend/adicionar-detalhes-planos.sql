-- Script SQL para adicionar detalhes aos planos existentes
-- Execute este script no banco de dados para popular os detalhes

-- Limpar detalhes antigos (se houver)
DELETE FROM details_plan WHERE plan_id IN (1, 2, 3);

-- Detalhes do Plano Grátis (ID = 1)
INSERT INTO details_plan (plan_id, name, description, created_at, updated_at) VALUES
(1, 'Até 50 produtos cadastrados', 'Até 50 produtos cadastrados', NOW(), NOW()),
(1, '1 usuário', '1 usuário', NOW(), NOW()),
(1, '30 pedidos por mês', '30 pedidos por mês', NOW(), NOW()),
(1, 'Painel administrativo básico', 'Painel administrativo básico', NOW(), NOW()),
(1, 'Cardápio digital', 'Cardápio digital', NOW(), NOW()),
(1, 'Sem acesso a Marketing', 'Sem acesso a Marketing', NOW(), NOW()),
(1, 'Sem acesso a Relatórios', 'Sem acesso a Relatórios', NOW(), NOW());

-- Detalhes do Plano Básico (ID = 2)
INSERT INTO details_plan (plan_id, name, description, created_at, updated_at) VALUES
(2, 'Até 100 produtos cadastrados', 'Até 100 produtos cadastrados', NOW(), NOW()),
(2, 'Até 5 usuários simultâneos', 'Até 5 usuários simultâneos', NOW(), NOW()),
(2, '100 pedidos por mês', '100 pedidos por mês', NOW(), NOW()),
(2, 'Painel administrativo completo', 'Painel administrativo completo', NOW(), NOW()),
(2, 'Cardápio digital personalizado', 'Cardápio digital personalizado', NOW(), NOW()),
(2, '✅ Acesso a Marketing', '✅ Acesso a Marketing', NOW(), NOW()),
(2, '✅ Acesso a Relatórios', '✅ Acesso a Relatórios', NOW(), NOW()),
(2, 'Suporte por email', 'Suporte por email', NOW(), NOW());

-- Detalhes do Plano Premium (ID = 3)
INSERT INTO details_plan (plan_id, name, description, created_at, updated_at) VALUES
(3, 'Produtos ilimitados', 'Produtos ilimitados', NOW(), NOW()),
(3, 'Usuários ilimitados', 'Usuários ilimitados', NOW(), NOW()),
(3, 'Pedidos ilimitados', 'Pedidos ilimitados', NOW(), NOW()),
(3, 'Acesso a todas funcionalidades', 'Acesso a todas funcionalidades', NOW(), NOW()),
(3, '✅ Acesso a Marketing', '✅ Acesso a Marketing', NOW(), NOW()),
(3, '✅ Acesso a Relatórios', '✅ Acesso a Relatórios', NOW(), NOW()),
(3, 'Suporte prioritário via WhatsApp', 'Suporte prioritário via WhatsApp', NOW(), NOW()),
(3, 'Múltiplos usuários e permissões', 'Múltiplos usuários e permissões', NOW(), NOW()),
(3, 'Integrações com delivery', 'Integrações com delivery', NOW(), NOW()),
(3, 'Personalização completa da marca', 'Personalização completa da marca', NOW(), NOW()),
(3, 'Relatórios avançados e exportação', 'Relatórios avançados e exportação', NOW(), NOW()),
(3, 'Controle de estoque inteligente', 'Controle de estoque inteligente', NOW(), NOW());

-- Atualizar nomes e valores dos planos para ficarem corretos
UPDATE plans SET 
    name = 'Grátis',
    url = 'gratis',
    price = 0.00,
    description = 'Perfeito para testar o sistema',
    max_users = 1,
    max_products = 50,
    max_orders_per_month = 30,
    has_marketing = false,
    has_reports = false
WHERE id = 1;

UPDATE plans SET 
    name = 'Básico',
    url = 'basico',
    price = 49.90,
    description = 'Perfeito para pequenos negócios começando',
    max_users = 5,
    max_products = 100,
    max_orders_per_month = 100,
    has_marketing = true,
    has_reports = true
WHERE id = 2;

UPDATE plans SET 
    name = 'Premium',
    url = 'premium',
    price = 99.90,
    description = 'Solução completa para grandes operações',
    max_users = 999999,
    max_products = 999999,
    max_orders_per_month = NULL,
    has_marketing = true,
    has_reports = true
WHERE id = 3;

-- Verificar resultado
SELECT p.id, p.name, COUNT(d.id) as total_details 
FROM plans p 
LEFT JOIN details_plan d ON p.id = d.plan_id 
GROUP BY p.id, p.name;

