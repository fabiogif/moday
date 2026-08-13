-- Adicionar colunas faltantes na tabela plans
ALTER TABLE plans 
ADD COLUMN is_active BOOLEAN DEFAULT true,
ADD COLUMN max_users INTEGER,
ADD COLUMN max_products INTEGER,
ADD COLUMN max_orders_per_month INTEGER;

-- Atualizar planos existentes para serem ativos
UPDATE plans SET is_active = true WHERE is_active IS NULL;

-- Comentário: Execute este script no painel da Digital Ocean
-- Este script adiciona as colunas necessárias para o modelo Plan funcionar corretamente
