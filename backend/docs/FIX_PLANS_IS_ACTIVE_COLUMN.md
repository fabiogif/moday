# Correção: Coluna is_active na tabela plans

## Problema Identificado

O endpoint `/api/plans` estava retornando erro:
```
SQLSTATE[42703]: Undefined column: 7 ERROR: column "is_active" does not exist
```

## Causa Raiz

O modelo `Plan` e o controlador `RegisterController` estavam tentando usar a coluna `is_active` que não existia na tabela `plans`.

## Solução Implementada

### 1. Arquivo SQL criado: `add_columns_to_plans.sql`

Execute este script no painel da Digital Ocean para adicionar as colunas faltantes:

```sql
ALTER TABLE plans 
ADD COLUMN is_active BOOLEAN DEFAULT true,
ADD COLUMN max_users INTEGER,
ADD COLUMN max_products INTEGER,
ADD COLUMN max_orders_per_month INTEGER;

UPDATE plans SET is_active = true WHERE is_active IS NULL;
```

### 2. Migration criada para histórico

Foi criada a migration: `2025_10_13_225637_add_missing_columns_to_plans_table.php`

Esta migration pode ser executada localmente com:
```bash
php artisan migrate
```

## Colunas Adicionadas

- `is_active` (BOOLEAN, default: true) - Indica se o plano está ativo
- `max_users` (INTEGER, nullable) - Limite máximo de usuários
- `max_products` (INTEGER, nullable) - Limite máximo de produtos
- `max_orders_per_month` (INTEGER, nullable) - Limite máximo de pedidos por mês

## Arquivos Modificados

- ✅ `database/migrations/2025_10_13_225637_add_missing_columns_to_plans_table.php` (criado)
- ✅ `add_columns_to_plans.sql` (criado)

## Verificação

Após executar o script SQL, o endpoint deve funcionar corretamente:

```bash
curl https://orca-app-7hejo.ondigitalocean.app/api/plans
```

## Próximos Passos

1. Execute o script SQL no painel da Digital Ocean
2. Verifique se o endpoint `/api/plans` retorna os dados corretamente
3. Configure os planos com os limites adequados usando as novas colunas
