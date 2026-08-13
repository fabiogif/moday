# Correções de Status Order e Permissões

## Resumo
Este documento detalha todas as correções realizadas para resolver problemas relacionados ao status de pedidos (Orders) e permissões de usuários no sistema.

## Problemas Identificados

### 1. Erro ao Criar Pedido - Status Inválido
**Erro:** 
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

**Causa:** 
O código estava tentando usar o status "Em Andamento" que não existe mais no ENUM da tabela `orders`. A migração `2025_10_03_223000_update_orders_status_enum.php` atualizou os valores do ENUM, removendo "Em Andamento" e adicionando novos status.

**Status válidos no banco de dados:**
- Preparo
- Pronto
- Entregue
- Pendente
- Em Preparo
- Completo
- Cancelado
- Rejeitado
- Em Entrega

### 2. Erro de Permissão users.index
**Erro:**
```
Acesso negado. Permissão necessária: users.index
```

**Causa:** 
Embora a permissão `users.index` existisse no sistema, ela não estava sendo verificada corretamente ou o usuário não tinha as permissões associadas ao seu perfil.

### 3. Erro na Migration de payment_methods
**Erro:**
```
SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'status'
```

**Causa:**
O método `down()` da migration estava tentando adicionar a coluna `status` antes de `flag`, mas a ordem estava invertida em relação ao método `up()`.

## Correções Realizadas

### Backend

#### 1. OrderService.php
**Arquivo:** `backend/app/Services/OrderService.php`

**Alteração:**
```php
// ANTES
$status = 'Em Andamento';

// DEPOIS
$status = 'Preparo';
```

**Linha 34:** Alterado o status padrão de novos pedidos de "Em Andamento" para "Preparo".

#### 2. UpdateOrderRequest.php
**Arquivo:** `backend/app/Http/Requests/Api/UpdateOrderRequest.php`

**Alterações:**
```php
// ANTES
'status' => 'sometimes|required|string|in:Em Preparo,Pronto,Entregue,Cancelado,Pendente,Completo,Cancelado,Rejeitado,Em Andamento,Em Entrega.'

// DEPOIS
'status' => 'sometimes|required|string|in:Preparo,Pronto,Entregue,Cancelado,Pendente,Em Preparo,Completo,Rejeitado,Em Entrega'
```

**Linha 27:** Removido "Em Andamento" e atualizado lista de status válidos.
**Linha 49:** Atualizada mensagem de erro com os status corretos.

#### 3. TableRepository.php
**Arquivo:** `backend/app/Repositories/TableRepository.php`

**Alteração:**
```php
// ANTES
->whereIn('status', ['Pendente', 'Em Andamento', 'Em Entrega'])

// DEPOIS
->whereIn('status', ['Pendente', 'Preparo', 'Em Preparo', 'Em Entrega'])
```

**Linha 49:** Atualizado filtro de status para cálculo de mesas ocupadas.

#### 4. OrderFactory.php
**Arquivo:** `backend/database/factories/OrderFactory.php`

**Alteração:**
```php
// ANTES
'status'=> 'Em Andamento',

// DEPOIS
'status'=> 'Preparo',
```

**Linha 25:** Atualizado status padrão no factory.

#### 5. Migration payment_methods
**Arquivo:** `backend/database/migrations/2025_10_03_155218_remove_status_column_from_payment_methods_table.php`

**Alteração:**
```php
// ANTES (método down)
// Adicionava flag primeiro, depois status

// DEPOIS
// Adicionava status primeiro, depois flag (ordem invertida)
```

**Linhas 50-65:** Invertida a ordem de adição das colunas no método `down()` para corresponder à ordem do método `up()`.

### Frontend

#### 1. Order Types
**Arquivo:** `frontend/src/app/(dashboard)/orders/types.ts`

**Alteração:**
```typescript
// ANTES
export type OrderStatus = 'Pendente' | 'Completo' | 'Cancelado' | 'Rejeitado' | 'Em Andamento' | 'Em Entrega'

// DEPOIS
export type OrderStatus = 'Preparo' | 'Pronto' | 'Entregue' | 'Pendente' | 'Em Preparo' | 'Completo' | 'Cancelado' | 'Rejeitado' | 'Em Entrega'
```

**Linha 1:** Atualizado tipo TypeScript com os status válidos do backend.

### Seeders (Verificados - Já Estavam Corretos)

#### 1. PermissionSeeder.php
✅ Já contém todas as permissões necessárias, incluindo:
- users.index
- users.show
- users.store
- users.update
- users.destroy
- users.change-password
- users.assign-profile

#### 2. AssignAllPermissionsToProfileSeeder.php
✅ Vincula automaticamente todas as permissões ao perfil "Super Admin" (ID 1)

#### 3. UsersTableSeeder.php
✅ Cria o usuário fabio@fabio.com e vincula ao perfil "Super Admin"

## Verificação Pós-Correção

### Banco de Dados

1. **Estrutura da coluna status em orders:**
```sql
Type: enum('Preparo','Pronto','Entregue','Pendente','Em Preparo','Completo','Cancelado','Rejeitado','Em Entrega')
Default: Pendente
```

2. **Permissões vinculadas ao perfil Super Admin:**
- Total de permissões: 81
- Tabela: `permission_profiles`
- Profile ID: 1

3. **Usuário fabio@fabio.com:**
- Email: fabio@fabio.com
- Perfil: Super Admin (ID: 1)
- Todas as 81 permissões disponíveis através do perfil

## Comandos Executados

```bash
# Executar seeders
docker-compose exec laravel.test php artisan db:seed --class=PermissionSeeder
docker-compose exec laravel.test php artisan db:seed --class=AssignAllPermissionsToProfileSeeder
docker-compose exec laravel.test php artisan db:seed --class=UsersTableSeeder
```

## Status Final

✅ **Problema 1 - Status Order:** RESOLVIDO
- Código não usa mais "Em Andamento"
- Frontend e backend sincronizados com os status corretos

✅ **Problema 2 - Permissão users.index:** RESOLVIDO
- Permissão existe no sistema
- Vinculada ao perfil Super Admin
- Usuário fabio@fabio.com tem acesso

✅ **Problema 3 - Migration payment_methods:** RESOLVIDO
- Ordem correta no método down()

## Próximos Passos Recomendados

1. Testar criação de pedidos no frontend
2. Verificar se todas as ações de usuário estão funcionando (editar, excluir, alterar senha, vincular perfil)
3. Validar que o status dos pedidos é atualizado corretamente
4. Confirmar que não há mais erros de permissão ao acessar a página de usuários

## Notas Importantes

- Não é necessário executar migrations novamente, pois todas as tabelas já estão atualizadas
- O perfil "Super Admin" tem todas as permissões do sistema
- Qualquer novo usuário que precise de acesso completo deve ser vinculado ao perfil "Super Admin"
- Se precisar adicionar novas permissões no futuro, execute os seeders na seguinte ordem:
  1. PermissionSeeder
  2. AssignAllPermissionsToProfileSeeder
