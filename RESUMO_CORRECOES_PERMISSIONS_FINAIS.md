# Resumo das Correções em Permissions

## Data: $(date +%Y-%m-%d)

## Mudanças Realizadas

### 1. Backend - Model Permission

**Arquivo:** `backend/app/Models/Permission.php`

Restaurados os campos completos no fillable:
- `name`
- `slug`
- `description`
- `module`
- `action`
- `resource`
- `group`
- `is_active`
- `tenant_id`

### 2. Backend - Migration

**Removido:** `backend/database/migrations/2025_10_04_232717_remove_unnecessary_fields_from_permissions_table.php`

Esta migration estava removendo campos essenciais (description, module, action, resource, group) que são necessários para o sistema de permissões.

### 3. Backend - PermissionSeeder

**Arquivo:** `backend/database/seeders/PermissionSeeder.php`

Adicionadas permissões completas para todos os módulos da aplicação:

#### Módulos com Permissões

1. **Clientes** (clients)
   - index, show, store, update, destroy

2. **Produtos** (products)
   - index, show, store, update, destroy

3. **Categorias** (categories)
   - index, show, store, update, destroy

4. **Mesas** (tables)
   - index, show, store, update, destroy

5. **Pedidos** (orders)
   - index, show, store, update, destroy, status

6. **Relatórios** (reports)
   - index, generate

7. **Usuários** (users)
   - index, show, store, update, destroy, change-password, assign-profile

8. **Perfis** (profiles)
   - index, show, store, update, destroy, assign-permissions

9. **Permissões** (permissions)
   - index, show, store, update, destroy

10. **Métodos de Pagamento** (payment-methods)
    - index, show, store, update, destroy

11. **Planos** (plans)
    - index, show, store, update, destroy

12. **Tenants** (tenants)
    - index, show, store, update, destroy

### 4. Estrutura de Permissões

Cada permissão possui:
```php
[
    'name' => 'Nome Descritivo',
    'slug' => 'module.action',  // Ex: users.index
    'description' => 'Descrição do que a permissão permite',
    'module' => 'module',       // Ex: users
    'action' => 'action',       // Ex: index, store, update, destroy
    'resource' => 'resource',   // Ex: user
    'is_active' => true
]
```

## Rotas da API

### Profiles e Permissions

#### Perfis
```
GET    /api/profiles                          - Listar perfis
POST   /api/profiles                          - Criar perfil
GET    /api/profiles/{profile}                - Ver detalhes do perfil
PUT    /api/profiles/{profile}                - Atualizar perfil
DELETE /api/profiles/{profile}                - Excluir perfil
```

#### Permissões do Perfil
```
GET    /api/profiles/{profile}/permissions                - Listar permissões do perfil
GET    /api/profiles/{profile}/permissions/available      - Listar permissões disponíveis
POST   /api/profiles/{profile}/permissions                - Vincular permissão
DELETE /api/profiles/{profile}/permissions/{permission}   - Desvincular permissão
PUT    /api/profiles/{profile}/permissions/sync           - Sincronizar permissões
```

#### Permissões
```
GET    /api/permissions                       - Listar permissões
POST   /api/permissions                       - Criar permissão
GET    /api/permissions/{id}                  - Ver detalhes da permissão
PUT    /api/permissions/{id}                  - Atualizar permissão
DELETE /api/permissions/{id}                  - Excluir permissão
GET    /api/permissions/{id}/usage            - Ver uso da permissão
GET    /api/permissions/{id}/profiles         - Ver perfis com a permissão
```

## Frontend

### Componente: assign-permissions-dialog.tsx

O componente já está preparado para:
- Carregar todas as permissões disponíveis
- Filtrar permissões por nome, slug, descrição ou módulo
- Agrupar permissões por módulo
- Selecionar/deselecionar todas as permissões
- Sincronizar permissões selecionadas com o perfil

#### Endpoint utilizado
```typescript
PUT /api/profiles/{profileId}/permissions/sync
Body: { permission_ids: [1, 2, 3, ...] }
```

## Problemas Identificados

### 1. Erro "Perfil não encontrado"

**Causa:** O route model binding do Laravel está tentando encontrar o perfil mas retorna 404.

**Possíveis causas:**
- Perfil não pertence ao tenant do usuário autenticado
- ID do perfil incorreto

**Solução:** Verificar no controller `PermissionProfileApiController::syncPermissionsForProfile` se há algum problema com o route model binding.

### 2. Permissões não carregando no dialog

**Status:** Resolvido - O código já está correto e extrai as permissões do objeto retornado pela API corretamente.

## Próximos Passos

1. ✅ Reverter migration que removeu campos
2. ✅ Atualizar Model Permission com todos os campos
3. ✅ Atualizar PermissionSeeder com todas as permissões
4. ⏳ Rodar migrations e seeders no banco
5. ⏳ Testar vinculação de permissões ao perfil
6. ⏳ Implementar página de usuários
7. ⏳ Implementar vinculação de perfil ao usuário

## Comandos para Executar (quando banco estiver disponível)

```bash
cd backend

# Rodar migrations
php artisan migrate

# Rodar seeders
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=AssignAllPermissionsToProfileSeeder

# Verificar permissões criadas
php artisan tinker
>>> Permission::count()
>>> Permission::where('tenant_id', 1)->get(['name', 'slug', 'module', 'action'])
```

## Observações

- O sistema usa a estrutura **User -> Profile -> Permissions**
- Cada usuário pode ter um ou mais perfis
- Cada perfil tem um conjunto de permissões
- As permissões são verificadas pelo slug (ex: `users.index`)
- Todas as entidades são multi-tenant (isolamento por tenant_id)
