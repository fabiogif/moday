-- Script para adicionar a coluna payment_method_id à tabela orders
-- Execute este script no banco de dados de produção

-- Adicionar a coluna payment_method_id (tipo UUID para compatibilidade com payment_methods.uuid)
ALTER TABLE orders ADD COLUMN payment_method_id UUID NULL;

-- Adicionar índice para melhor performance
CREATE INDEX idx_orders_payment_method_id ON orders(payment_method_id);

-- Adicionar foreign key constraint (opcional, mas recomendado)
-- Descomente a linha abaixo se quiser adicionar a constraint de foreign key
-- ALTER TABLE orders ADD CONSTRAINT fk_orders_payment_method_id FOREIGN KEY (payment_method_id) REFERENCES payment_methods(uuid) ON DELETE SET NULL;

-- Verificar se a coluna foi adicionada corretamente
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name = 'orders' 
AND column_name = 'payment_method_id';
