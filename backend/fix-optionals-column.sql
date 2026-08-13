-- Adicionar coluna optionals se não existir
-- Execute este arquivo para garantir que a coluna optionals está presente

-- Verificar se a coluna já existe
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_COMMENT 
FROM 
    information_schema.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'laravel' 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME IN ('variations', 'optionals');

-- Se optionals não aparecer acima, execute:
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS optionals JSON NULL 
COMMENT 'Opcionais do produto (seleção múltipla com quantidade)' 
AFTER variations;

-- Verificar novamente
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE 
FROM 
    information_schema.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'laravel' 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME IN ('variations', 'optionals')
ORDER BY ORDINAL_POSITION;

-- Deve retornar:
-- variations | json | YES
-- optionals  | json | YES

