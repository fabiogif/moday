# Migration: Adicionar campo deleted_at à tabela products

## Problema Identificado

O sistema estava apresentando o seguinte erro ao listar produtos e pedidos:

```
SQLSTATE[42703]: Undefined column: 7 ERROR: column products.deleted_at does not exist
```

Este erro ocorria porque:
1. O modelo `Product` estava tentando usar o campo `deleted_at` nas queries
2. Uma migration anterior (`2025_10_13_195001_remove_deleted_at_from_products_table.php`) havia removido este campo da tabela
3. O modelo não estava configurado para usar `SoftDeletes`

## Solução Implementada

### 1. Criada nova migration

Arquivo: `database/migrations/2025_10_13_195456_add_deleted_at_back_to_products_table.php`

Esta migration adiciona o campo `deleted_at` de volta à tabela `products`, permitindo o uso de soft deletes.

### 2. Atualizado o modelo Product

Arquivo: `app/Models/Product.php`

Adicionado o trait `SoftDeletes` ao modelo:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    // ...
}
```

## Como Executar em Produção

### Para ambiente Docker/Sail (Desenvolvimento)

```bash
cd backend
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

### Para ambiente de Produção (Digital Ocean)

1. Fazer SSH no servidor ou usar o console do App Platform

2. Executar a migration:
```bash
php artisan migrate --force
```

3. Verificar se a migration foi executada com sucesso:
```bash
php artisan migrate:status
```

### Alternativa: Deploy automático

Se você tiver configurado deploy automático, basta fazer push para o repositório:

```bash
git add .
git commit -m "fix: adicionar campo deleted_at à tabela products"
git push origin main
```

## Verificação

Após executar a migration, verifique se o campo foi criado:

```sql
-- No PostgreSQL (Produção)
\d products

-- No MySQL (Desenvolvimento)
DESCRIBE products;
```

O campo `deleted_at` deve aparecer como `timestamp` nullable.

## Impacto

- **Produtos**: Agora suportam soft delete (exclusão lógica)
- **Pedidos**: Não terão mais erro ao carregar produtos relacionados
- **Listagens**: Produtos excluídos logicamente não aparecerão nas listagens

## Observações

- Esta correção resolve os erros de "column products.deleted_at does not exist"
- Produtos que já foram "excluídos" anteriormente não serão afetados (não tinham soft delete)
- A partir de agora, quando um produto for excluído, ele será marcado com `deleted_at` ao invés de ser removido fisicamente do banco
