# Correções Realizadas - Sistema de Permissões

## Resumo

Foram realizadas correções no sistema de permissões para resolver os problemas de acesso negado na página de usuários e vinculação de permissões aos perfis.

## Alterações Realizadas

### 1. Backend - Middleware de Permissões ✅

**Arquivo**: `backend/app/Http/Middleware/PermissionMiddleware.php`

**Problema**: O middleware estava chamando o método `hasPermission()` que não existe no modelo `User`.

**Solução**: Alterado para `hasPermissionTo()` que é o método correto implementado no modelo.

```php
// Antes
if (!$user->hasPermission($permission)) {

// Depois
if (!$user->hasPermissionTo($permission)) {
```

### 2. Backend - Permissões de Usuários ✅

**Problema**: Existiam permissões antigas no banco de dados com slugs incorretos (`users.view`, `users.create`, `users.edit`, `users.delete`).

**Solução**: 
- Removidas permissões antigas
- Criadas novas permissões com slugs corretos conforme padrão RESTful:
  - `users.index` - Visualizar Usuários (lista)
  - `users.show` - Ver Detalhes do Usuário
  - `users.store` - Criar Usuários
  - `users.update` - Editar Usuários
  - `users.destroy` - Excluir Usuários
  - `users.change-password` - Alterar Senha de Usuário
  - `users.assign-profile` - Vincular Perfil ao Usuário

### 3. Backend - Atribuição de Permissões aos Perfis ✅

**Solução**:
- Executado seeder `AssignAllPermissionsToProfileSeeder` para atribuir todas as 48 permissões ao perfil de Administrador
- Atribuído perfil de Administrador aos usuários existentes (ID 1 e 2)

### 4. Frontend - Componente de Vinculação de Permissões ✅

**Arquivo**: `frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx`

**Problema**: A função `filterPermissions` estava tendo dificuldade em extrair o array de permissões da resposta da API.

**Solução**: Simplificada a lógica de extração de dados. A API retorna `{ permissions: [...], pagination: {...} }` e agora o código extrai corretamente a propriedade `permissions`.

## Status das Funcionalidades

### ✅ Resolvido
- Erro ao carregar usuários: "Acesso negado. Permissão necessária: users.index"
- Middleware de permissões funcionando corretamente
- Permissões corretas no banco de dados
- Perfis atribuídos aos usuários
- Extração de permissões no frontend

### Funcionalidades Disponíveis

#### Página de Usuários
A página de usuários (`/users`) agora mostra:
- Nome
- Email
- Status (is_active)
- Data de Criação
- Data de Alteração
- Perfis vinculados

#### Ações Disponíveis
- ✅ Editar usuário
- ✅ Excluir usuário
- ✅ Alterar senha
- ✅ Vincular perfil ao usuário

#### Página de Perfis
A página de perfis (`/profiles`) permite:
- ✅ Listar perfis
- ✅ Criar perfil
- ✅ Editar perfil
- ✅ Excluir perfil
- ✅ Vincular permissões ao perfil (modal de seleção múltipla)

## Teste de Verificação

Para verificar se tudo está funcionando, execute:

```bash
cd backend
docker-compose exec laravel.test php artisan tinker
```

Depois execute:

```php
$user = App\Models\User::find(2);
echo "Usuário: " . $user->name . "\n";
echo "Permissões de usuários:\n";
echo "  users.index: " . ($user->hasPermissionTo('users.index') ? 'Sim' : 'Não') . "\n";
echo "  users.store: " . ($user->hasPermissionTo('users.store') ? 'Sim' : 'Não') . "\n";
```

Resultado esperado: Todas as permissões devem retornar "Sim".

## Estrutura de Permissões

O sistema agora segue a estrutura recomendada:

```
User -> Profile -> Permissions
```

- Um usuário pode ter múltiplos perfis
- Um perfil pode ter múltiplas permissões
- As permissões são verificadas através dos perfis do usuário

## Permissões por Módulo

Todas as permissões seguem o padrão: `{module}.{action}`

### Usuários (users)
- `users.index` - Listar usuários
- `users.show` - Ver detalhes
- `users.store` - Criar
- `users.update` - Editar
- `users.destroy` - Excluir
- `users.change-password` - Alterar senha
- `users.assign-profile` - Vincular perfil

### Perfis (profiles)
- `profiles.index` - Listar perfis
- `profiles.show` - Ver detalhes
- `profiles.store` - Criar
- `profiles.update` - Editar
- `profiles.destroy` - Excluir
- `profiles.assign-permissions` - Vincular permissões

### Permissões (permissions)
- `permissions.index` - Listar permissões
- `permissions.show` - Ver detalhes
- `permissions.store` - Criar
- `permissions.update` - Editar
- `permissions.destroy` - Excluir

### Outros Módulos
- Clientes (`clients.*`)
- Produtos (`products.*`)
- Categorias (`categories.*`)
- Mesas (`tables.*`)
- Pedidos (`orders.*`)
- Relatórios (`reports.*`)
- Métodos de Pagamento (`payment-methods.*`)
- Planos (`plans.*`)
- Tenants (`tenants.*`)

## Próximos Passos

1. ✅ Sistema de permissões funcionando
2. ✅ Página de usuários operacional
3. ✅ Vinculação de perfis aos usuários
4. ✅ Vinculação de permissões aos perfis

## Observações Importantes

- O perfil "Administrador" (ID: 1) possui TODAS as 48 permissões do sistema
- Os usuários ID 1 e 2 estão vinculados ao perfil "Administrador"
- Ao criar novos usuários, lembre-se de atribuir pelo menos um perfil
- As permissões são verificadas através dos perfis, não diretamente no usuário
- O slug das permissões segue o padrão RESTful (index, show, store, update, destroy)
