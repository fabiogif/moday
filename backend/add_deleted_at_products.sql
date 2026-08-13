-- Script SQL para adicionar coluna deleted_at na tabela products se não existir
-- Execute este script diretamente no PostgreSQL da DigitalOcean

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name='products' 
        AND column_name='deleted_at'
    ) THEN
        ALTER TABLE products ADD COLUMN deleted_at TIMESTAMP NULL;
        RAISE NOTICE 'Coluna deleted_at adicionada com sucesso à tabela products';
    ELSE
        RAISE NOTICE 'Coluna deleted_at já existe na tabela products';
    END IF;
END $$;

-- Verificar se a coluna foi criada
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'products'
AND column_name = 'deleted_at';
