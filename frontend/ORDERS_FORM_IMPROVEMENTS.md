# Melhorias no Formulário de Pedidos

## Resumo das Alterações

Este documento descreve as melhorias implementadas no formulário de criação de pedidos (`order-form-dialog.tsx`).

## Funcionalidades Implementadas

### 1. ✅ Seleção de Cliente (em vez de campos manuais)

- **Antes**: Campos separados para nome e email do cliente
- **Agora**: Lista dropdown com clientes cadastrados e ativos
- Exibe nome e email de cada cliente na lista
- Filtra apenas clientes ativos (`isActive: true`)

### 2. ✅ Modal de Cadastro Rápido de Cliente

- Botão com ícone `UserPlus` ao lado do seletor de clientes
- Modal overlay que permite criar um novo cliente sem sair do formulário de pedido
- Campos: Nome, Email, Telefone, Endereço
- Após criar o cliente, a lista é atualizada automaticamente
- Modal com validação básica (requer nome, email e telefone)

### 3. ✅ Seleção Múltipla de Produtos

- Possibilidade de adicionar múltiplos produtos ao pedido
- Cada produto em um card separado com:
  - Seletor de produto (mostra nome, preço e estoque disponível)
  - Campo de quantidade
  - Preço unitário (read-only)
  - Subtotal calculado automaticamente
- Botão "Adicionar Produto" para incluir mais itens
- Botão de remoção (ícone de lixeira) para cada produto (exceto se houver apenas um)
- Mínimo de 1 produto obrigatório

### 4. ✅ Cálculo Automático do Total

- O total é calculado automaticamente ao:
  - Selecionar um produto
  - Alterar a quantidade
  - Adicionar/remover produtos
- Campo Total é read-only e formatado como moeda (R$)
- Exibido em destaque com fonte maior

### 5. ✅ Campo Total Movido para o Final

- O campo Total agora é o último campo do formulário
- Fica antes apenas dos botões de ação (Cancelar/Criar)

### 6. ✅ Campo Delivery (Toggle)

- Switch para indicar se o pedido é para entrega
- Exibido como um item destacado com borda

### 7. ✅ Campo Mesa Condicional

- Aparece **apenas** quando Delivery está desativado
- Lista todas as mesas cadastradas
- Exibe: Nome da mesa, identificador e capacidade
- Campo obrigatório quando não for delivery (validação via Zod)

## Estrutura do Formulário (Ordem dos Campos)

1. **Cliente** (com botão de adicionar novo)
2. **Produtos** (múltiplos, com adicionar/remover)
   - Produto
   - Quantidade
   - Preço Unitário
   - Subtotal
3. **Status** (Pendente, Em Preparo, Pronto, Entregue, Cancelado)
4. **Delivery** (Switch)
5. **Mesa** (condicional - apenas se não for delivery)
6. **Total** (calculado automaticamente, read-only)

## Validações Implementadas

### Schema Zod (`orderFormSchema`)

```typescript
- clientId: obrigatório (string)
- products: array com mínimo de 1 produto
  - productId: obrigatório
  - quantity: mínimo 1
  - price: mínimo 0
- status: obrigatório
- isDelivery: boolean
- tableId: opcional, mas obrigatório quando isDelivery = false
- total: mínimo 0
```

### Validação Customizada

- Se `isDelivery = false`, o campo `tableId` é obrigatório
- Implementado com `.refine()` do Zod

## Integrações com API

### Hooks Utilizados

- `useClients()`: Busca lista de clientes
- `useProducts()`: Busca lista de produtos com preços e estoque
- `useTables()`: Busca lista de mesas disponíveis
- `useMutation()`: Para criar novo cliente

### Endpoints

- `endpoints.clients.create`: Criar novo cliente
- Dados enviados ao criar pedido incluem todos os campos do formulário

## Componentes UI Utilizados

- `Dialog`: Modal principal do formulário
- `Form` (react-hook-form): Gerenciamento de estado do formulário
- `Select`: Dropdowns para Cliente, Produtos, Status, Mesa
- `Switch`: Toggle para Delivery
- `Input`: Campos numéricos e texto
- `Card`: Container para cada produto
- `Badge`: Exibição de estoque disponível
- `Button`: Ações e adição de produtos
- Custom overlay modal: Para cadastro rápido de cliente

## Melhorias de UX

1. **Feedback Visual**

   - Subtotal exibido para cada produto
   - Total destacado em fonte maior
   - Badge mostrando estoque disponível
   - Ícones intuitivos (UserPlus, Plus, Trash2)

2. **Scroll Responsivo**

   - Modal com altura máxima e scroll automático
   - Largura responsiva (max-w-600px)

3. **Validação em Tempo Real**

   - Mensagens de erro do Zod
   - Botão de criar cliente desabilitado se faltar dados

4. **Atualização Automática**
   - Lista de clientes atualiza após criar novo
   - Total recalcula automaticamente

## Próximos Passos Sugeridos

- [ ] Adicionar filtro de mesas disponíveis (excluir mesas ocupadas)
- [ ] Implementar edição de pedidos existentes
- [ ] Adicionar campo de observações do pedido
- [ ] Implementar desconto/cupom
- [ ] Adicionar taxa de entrega para pedidos delivery
- [ ] Salvar endereço de entrega quando for delivery

## Notas Técnicas

- Utiliza `useFieldArray` do react-hook-form para gerenciar array de produtos
- `useEffect` para recalcular total quando produtos mudam
- Z-index 100 para modal de cliente sobrepor o modal de pedido
- State local para campos do novo cliente (evita conflito com form principal)
