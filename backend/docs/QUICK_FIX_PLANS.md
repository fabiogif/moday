# 🚀 Correção Rápida - Endpoint de Planos

## ⚡ Ação Imediata Necessária

Execute este SQL no painel da Digital Ocean:

```sql
ALTER TABLE plans 
ADD COLUMN is_active BOOLEAN DEFAULT true,
ADD COLUMN max_users INTEGER,
ADD COLUMN max_products INTEGER,
ADD COLUMN max_orders_per_month INTEGER;

UPDATE plans SET is_active = true WHERE is_active IS NULL;
```

## 📋 Como Executar

1. Acesse: https://cloud.digitalocean.com/
2. Vá para o seu banco de dados PostgreSQL
3. Clique em "Query" ou "Console"
4. Cole o SQL acima
5. Execute

## ✅ Verificação

Após executar, teste:
```bash
curl https://orca-app-7hejo.ondigitalocean.app/api/plans
```

Deve retornar JSON com a lista de planos em vez de erro.

## 📚 Documentação Completa

- `SOLUCAO_PLANS_ENDPOINT.md` - Solução completa
- `FIX_PLANS_IS_ACTIVE_COLUMN.md` - Detalhes técnicos
- `add_columns_to_plans.sql` - Script SQL standalone
