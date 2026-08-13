# Alterações no Fluxo de Status de Pedidos

## Resumo das Mudanças

O fluxo de status dos pedidos foi alterado de:
- **Antigo**: Pendente → Completo → Cancelado / Rejeitado / Em Andamento / Em Entrega
- **Novo**: Em Preparo → Pronto → Entregue / Cancelado

## Arquivos Modificados

### 1. Migrações
- **2024_05_26_204101_create_orders_table.php**: Atualizado ENUM inicial para os novos status
- **2025_10_05_015016_fix_orders_status_to_correct_flow.php**: Nova migração que:
  - Converte dados existentes ('Pendente'/'Em Andamento' → 'Em Preparo')
  - Altera ENUM para os novos valores: `enum('Em Preparo','Pronto','Entregue','Cancelado')`

### 2. Services
- **app/Services/OrderService.php**:
  - Status padrão alterado de 'Pendente' para 'Em Preparo'
  - Estatísticas atualizadas para refletir os novos status:
    - `pending_orders` → `in_preparo_orders`
    - `paid_orders` → `pronto_orders`
    - Mantido: `delivered_orders`, `canceled_orders`

### 3. Repositories
- **app/Repositories/TableRepository.php**:
  - Lógica de mesas ocupadas atualizada para usar status 'Em Preparo' e 'Pronto'

### 4. Controllers
- **app/Http/Controllers/Api/OrderApiController.php**:
  - Método `invoice()` atualizado para usar 'Entregue' ao invés de 'Completo'

- **app/Http/Controllers/Api/OrderStatsApiController.php**:
  - Documentação OpenAPI atualizada para refletir novos nomes de campos e status

- **app/Http/Controllers/Api/DashboardApiController.php**:
  - Documentação OpenAPI atualizada para refletir novos status

### 5. Validações
- **app/Http/Requests/Api/UpdateOrderRequest.php**:
  - Regra de validação do campo `status` atualizada para aceitar apenas: `Em Preparo, Pronto, Entregue, Cancelado`
  - Mensagens de erro atualizadas

## Novos Status

| Status | Descrição | Quando usar |
|--------|-----------|-------------|
| Em Preparo | Pedido recebido e está sendo preparado | Status inicial ao criar pedido |
| Pronto | Pedido finalizado, aguardando entrega/retirada | Quando preparação estiver completa |
| Entregue | Pedido entregue ao cliente | Ao entregar/concluir o pedido (equivalente ao antigo "Completo") |
| Cancelado | Pedido cancelado | Quando o pedido for cancelado |

## Fluxo Recomendado

1. **Criação do Pedido**: Status inicial → `Em Preparo`
2. **Preparação Concluída**: Atualizar para → `Pronto`
3. **Entrega/Retirada**: Atualizar para → `Entregue`
4. **Cancelamento** (qualquer momento): Atualizar para → `Cancelado`

## Compatibilidade

A migração foi criada para converter automaticamente os dados existentes:
- Pedidos com status 'Pendente' ou 'Em Andamento' são convertidos para 'Em Preparo'
- A migração possui rollback que reverte as mudanças se necessário

## Como Executar

```bash
# Dentro do container Docker
docker-compose exec laravel.test php artisan migrate

# Ou se estiver no ambiente local com MySQL configurado
php artisan migrate
```

## Validação

Verifique o ENUM atual:
```sql
SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'status';
```

Resultado esperado:
```
enum('Em Preparo','Pronto','Entregue','Cancelado')
```

## Impacto no Frontend

Se houver interface frontend que exibe ou manipula status de pedidos, será necessário atualizar:
- Listas de seleção de status
- Filtros de status
- Badges/labels de exibição
- Qualquer lógica que dependa dos status antigos

## Testes Recomendados

1. Criar novo pedido e verificar se status inicial é 'Em Preparo'
2. Atualizar status do pedido para 'Pronto'
3. Atualizar status do pedido para 'Entregue'
4. Cancelar um pedido
5. Verificar estatísticas do dashboard
6. Verificar cálculo de mesas ocupadas

