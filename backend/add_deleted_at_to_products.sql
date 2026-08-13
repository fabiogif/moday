-- Script para adicionar deleted_at na tabela products
-- Execute este script diretamente no console do PostgreSQL do Digital Ocean

-- Verificar e adicionar a coluna deleted_at
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name='products' 
        AND column_name='deleted_at'
    ) THEN
        ALTER TABLE products ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;
        RAISE NOTICE 'Coluna deleted_at adicionada com sucesso';
    ELSE
        RAISE NOTICE 'Coluna deleted_at já existe';
    END IF;
END$$;

-- Criar índice para melhor performance em queries com SoftDeletes
CREATE INDEX IF NOT EXISTS idx_products_deleted_at ON products(deleted_at);

-- Verificação
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name='products' 
AND column_name='deleted_at';
