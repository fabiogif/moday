# Solução Completa: Endpoint de Planos

## 🔴 Problema

Erro ao acessar `https://orca-app-7hejo.ondigitalocean.app/api/plans`:
```json
{
    "success": false,
    "message": "Erro ao carregar planos",
    "error": "SQLSTATE[42703]: Undefined column: 7 ERROR: column \"is_active\" does not exist"
}
```

## 🔍 Diagnóstico

O modelo `Plan.php` e o método `RegisterController::plans()` esperam colunas que não existem na tabela `plans`:
- `is_active` (BOOLEAN)
- `max_users` (INTEGER)
- `max_products` (INTEGER)
- `max_orders_per_month` (INTEGER)

## ✅ Solução

### Passo 1: Execute o Script SQL no Digital Ocean

Criado o arquivo: `add_columns_to_plans.sql`

```sql
-- Adicionar colunas faltantes na tabela plans
ALTER TABLE plans 
ADD COLUMN is_active BOOLEAN DEFAULT true,
ADD COLUMN max_users INTEGER,
ADD COLUMN max_products INTEGER,
ADD COLUMN max_orders_per_month INTEGER;

-- Atualizar planos existentes para serem ativos
UPDATE plans SET is_active = true WHERE is_active IS NULL;
```

**Como executar no Digital Ocean:**
1. Acesse o painel da Digital Ocean
2. Vá para o banco de dados PostgreSQL
3. Abra a aba "Query"
4. Cole e execute o script acima

### Passo 2: Migration para Desenvolvimento Local

Criada a migration: `database/migrations/2025_10_13_225637_add_missing_columns_to_plans_table.php`

Para rodar localmente:
```bash
php artisan migrate
```

## 📊 Estrutura Atualizada da Tabela Plans

```sql
CREATE TABLE plans (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(191) UNIQUE NOT NULL,
    url VARCHAR(191) UNIQUE NOT NULL,
    description VARCHAR(191),
    price DOUBLE PRECISION(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    max_users INTEGER,
    max_products INTEGER,
    max_orders_per_month INTEGER,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## 🔗 Endpoints Disponíveis

### 1. Público - Lista Planos Ativos
```
GET /api/plans
```
Retorna apenas planos ativos, ordenados por preço.

### 2. API Completa - CRUD de Planos
```
GET    /api/public/plans          # Lista todos (paginado)
GET    /api/public/plans/{id}     # Detalhes de um plano
POST   /api/public/plans          # Criar plano (requer autenticação)
PUT    /api/public/plans/{id}     # Atualizar plano (requer autenticação)
DELETE /api/public/plans/{id}     # Deletar plano (requer autenticação)
```

## 🧪 Testar a Solução

```bash
# Teste simples
curl https://orca-app-7hejo.ondigitalocean.app/api/plans

# Teste com detalhes
curl -H "Accept: application/json" \
     https://orca-app-7hejo.ondigitalocean.app/api/plans
```

## 📝 Arquivos Criados/Modificados

✅ `add_columns_to_plans.sql` - Script SQL para produção
✅ `database/migrations/2025_10_13_225637_add_missing_columns_to_plans_table.php` - Migration
✅ `FIX_PLANS_IS_ACTIVE_COLUMN.md` - Documentação detalhada
✅ `SOLUCAO_PLANS_ENDPOINT.md` - Este arquivo

## 🎯 Próximos Passos

1. ✅ Execute o script SQL no Digital Ocean
2. ⏳ Verifique se o endpoint retorna dados corretamente
3. ⏳ Cadastre os planos no banco de dados
4. ⏳ Configure a landing page para listar os planos

## 💡 Observações Importantes

- A coluna `is_active` permite ativar/desativar planos sem deletá-los
- Os limites (`max_users`, `max_products`, `max_orders_per_month`) são nullable para flexibilidade
- Planos existentes serão automaticamente marcados como ativos
