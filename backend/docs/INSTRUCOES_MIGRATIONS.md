# Instruções para Executar Migrations no Painel Digital Ocean

## Migrations Criadas

Foram criadas duas migrations importantes para corrigir erros no sistema:

### 1. Migration para campo `deleted_at` na tabela `products`
- **Arquivo**: `2025_10_13_200147_ensure_products_deleted_at.php`
- **Objetivo**: Adicionar coluna `deleted_at` à tabela products para suportar soft deletes
- **Erro corrigido**: `SQLSTATE[42703]: Undefined column: 7 ERROR: column products.deleted_at does not exist`

### 2. Migration para campo `is_active` e outros na tabela `plans`
- **Arquivo**: `2025_10_13_200216_ensure_plans_is_active.php`
- **Objetivo**: Adicionar colunas `is_active`, `max_users`, `max_products`, `max_orders_per_month` à tabela plans
- **Erro corrigido**: `SQLSTATE[42703]: Undefined column: 7 ERROR: column "is_active" does not exist`

## Como Executar no Painel Digital Ocean

### Opção 1: Via Console do Pod

1. Acesse o console do pod no painel Digital Ocean
2. Execute o seguinte comando:

```bash
php artisan migrate
```

### Opção 2: Via kubectl (se tiver acesso)

```bash
kubectl exec -it <nome-do-pod> -- php artisan migrate
```

### Opção 3: Executar SQL Direto (Alternativa)

Se as migrations falharem, você pode executar o SQL direto no banco de dados:

#### Para a tabela `products`:
```sql
-- Adicionar deleted_at se não existir
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='products' AND column_name='deleted_at'
    ) THEN
        ALTER TABLE products ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;
    END IF;
END $$;
```

#### Para a tabela `plans`:
```sql
-- Adicionar is_active se não existir
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='plans' AND column_name='is_active'
    ) THEN
        ALTER TABLE plans ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
    END IF;
END $$;

-- Adicionar max_users se não existir
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='plans' AND column_name='max_users'
    ) THEN
        ALTER TABLE plans ADD COLUMN max_users INTEGER NULL;
    END IF;
END $$;

-- Adicionar max_products se não existir
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='plans' AND column_name='max_products'
    ) THEN
        ALTER TABLE plans ADD COLUMN max_products INTEGER NULL;
    END IF;
END $$;

-- Adicionar max_orders_per_month se não existir
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='plans' AND column_name='max_orders_per_month'
    ) THEN
        ALTER TABLE plans ADD COLUMN max_orders_per_month INTEGER NULL;
    END IF;
END $$;
```

## Verificação

Após executar as migrations, verifique se os campos foram criados:

```sql
-- Verificar campos da tabela products
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name='products' 
AND column_name IN ('deleted_at');

-- Verificar campos da tabela plans
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name='plans' 
AND column_name IN ('is_active', 'max_users', 'max_products', 'max_orders_per_month');
```

## Observações Importantes

- As migrations verificam se as colunas já existem antes de tentar adicioná-las, evitando erros de duplicação
- O campo `deleted_at` é essencial para o funcionamento do SoftDeletes no Laravel
- O campo `is_active` é usado para filtrar planos ativos na API
- Após executar as migrations, faça um deploy do backend para garantir que o código esteja sincronizado
