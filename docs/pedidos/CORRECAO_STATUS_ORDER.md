# Correção do Erro de Status do Pedido

## Problema Identificado

Ao criar um pedido, o sistema retornava o seguinte erro:

```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

## Causa Raiz

O problema estava relacionado a uma incompatibilidade entre o código e a estrutura do banco de dados:

1. **Migration Original** (`2024_05_26_204101_create_orders_table.php`):
   - Criava a coluna `status` como ENUM com os valores: `['Pendente', 'Completo', 'Cancelado', 'Rejeitado', 'Em Andamento', 'Em Entrega']`

2. **Migration de Update** (`2025_10_03_223000_update_orders_status_enum.php`):
   - Atualizava os registros existentes: `UPDATE orders SET status = 'Preparo' WHERE status = 'Em Andamento'`
   - Modificava o ENUM para: `['Preparo', 'Pronto', 'Entregue', 'Pendente', 'Em Preparo', 'Completo', 'Cancelado', 'Rejeitado', 'Em Entrega']`
   - **REMOVEU** o valor `'Em Andamento'` do ENUM

3. **Código no OrderService**:
   - Linha 34: `$status = 'Em Andamento';`
   - Tentava inserir um valor que **não existe mais** no ENUM

## Solução Aplicada

### Arquivo Modificado
`backend/app/Services/OrderService.php`

### Mudança Realizada
Linha 34:
```php
// ANTES
$status = 'Em Andamento';

// DEPOIS  
$status = 'Pendente';
```

## Justificativa

O status `'Pendente'` é o mais apropriado para novos pedidos porque:

1. É um valor válido no ENUM atualizado
2. Representa corretamente o estado inicial de um pedido recém-criado
3. Está alinhado com o fluxo de negócio esperado:
   - **Pendente** → novo pedido aguardando processamento
   - **Preparo** → pedido em preparação
   - **Pronto** → pedido finalizado
   - **Entregue** → pedido entregue ao cliente

## Valores de Status Disponíveis

Após a correção, os valores válidos de status são:

- `Preparo`
- `Pronto`
- `Entregue`
- `Pendente`
- `Em Preparo`
- `Completo`
- `Cancelado`
- `Rejeitado`
- `Em Entrega`

## Teste Recomendado

Para verificar se a correção funcionou:

1. Criar um novo pedido através da API
2. Verificar se o pedido é criado com sucesso
3. Confirmar que o status está definido como `'Pendente'`
4. Não deve mais aparecer o erro de truncamento de dados

## Arquivos Relacionados

- `backend/app/Services/OrderService.php` - Corrigido
- `backend/database/migrations/2024_05_26_204101_create_orders_table.php` - Migration original
- `backend/database/migrations/2025_10_03_223000_update_orders_status_enum.php` - Migration de update
