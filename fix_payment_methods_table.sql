-- SQL para remover a coluna flag da tabela payment_methods se ela existir
-- Execute este SQL diretamente no seu banco de dados

-- Verificar se a coluna flag existe e removê-la
SET @sql = (
    SELECT CASE 
        WHEN COUNT(*) > 0 THEN 'ALTER TABLE payment_methods DROP COLUMN flag;'
        ELSE 'SELECT "Column flag does not exist" as message;'
    END
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'payment_methods' 
    AND column_name = 'flag'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar se a coluna status existe e removê-la
SET @sql2 = (
    SELECT CASE 
        WHEN COUNT(*) > 0 THEN 'ALTER TABLE payment_methods DROP COLUMN status;'
        ELSE 'SELECT "Column status does not exist" as message;'
    END
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'payment_methods' 
    AND column_name = 'status'
);

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Mostrar a estrutura final da tabela
DESCRIBE payment_methods;