# Correções Realizadas - Profiles e Permissions

## Problemas Identificados e Soluções

### 1. Erro 404 ao vincular permissões ao perfil

**Problema:** 
```
ApiClient: Erro HTTP 404 : Perfil não encontrado
```

**Causa:** 
O frontend estava usando o endpoint `/api/profile/{id}/permissions/sync`, mas o backend espera `/api/profiles/{id}/permissions/sync`.

**Solução:**
Corrigido o endpoint no arquivo `frontend/src/lib/api-client.ts`:

```typescript
// Antes:
profiles: {
  base: '/api/profile',
  list: '/api/profile',
  // ...
}

// Depois:
profiles: {
  base: '/api/profiles',
  list: '/api/profiles',
  // ...
}
```

### 2. Permissões não sendo exibidas no modal "Vincular Permissões"

**Problema:**
O modal mostrava "Nenhuma permissão disponível" mesmo com permissões cadastradas no sistema.

**Causa:**
A função `filterPermissions()` não estava tratando corretamente os diferentes formatos de resposta da API. A API retorna:
```json
{
  "success": true,
  "data": {
    "permissions": [...],
    "pagination": {...}
  }
}
```

Mas o código esperava apenas o array diretamente.

**Solução:**
Melhorado o tratamento de dados no `assign-permissions-dialog.tsx`:

```typescript
const filterPermissions = () => {
  if (!allPermissions) {
    return []
  }

  let permissions: Permission[] = []
  
  // Tentar diferentes formatos de resposta
  if (Array.isArray(allPermissions)) {
    // Caso 1: allPermissions já é um array
    permissions = allPermissions
  } else if (typeof allPermissions === 'object') {
    // Caso 2: allPermissions é um objeto
    if ('permissions' in allPermissions && Array.isArray(allPermissions.permissions)) {
      // Tem a propriedade permissions que é um array
      permissions = allPermissions.permissions
    } else if ('data' in allPermissions) {
      // Tem a propriedade data
      const dataValue = (allPermissions as any).data
      if (Array.isArray(dataValue)) {
        permissions = dataValue
      } else if (dataValue && typeof dataValue === 'object' && 'permissions' in dataValue && Array.isArray(dataValue.permissions)) {
        permissions = dataValue.permissions
      }
    }
  }
  
  // Aplicar filtro de busca...
}
```

## Arquitetura Confirmada

O sistema utiliza a arquitetura recomendada:

```
User -> Profile -> Permissions
```

### Endpoints Disponíveis

#### Profiles
- `GET /api/profiles` - Listar perfis
- `POST /api/profiles` - Criar perfil
- `GET /api/profiles/{id}` - Visualizar perfil
- `PUT /api/profiles/{id}` - Atualizar perfil
- `DELETE /api/profiles/{id}` - Excluir perfil

#### Permissões do Perfil
- `GET /api/profiles/{id}/permissions` - Listar permissões do perfil
- `GET /api/profiles/{id}/permissions/available` - Listar permissões disponíveis
- `POST /api/profiles/{id}/permissions` - Adicionar permissão ao perfil
- `DELETE /api/profiles/{id}/permissions/{permissionId}` - Remover permissão do perfil
- `PUT /api/profiles/{id}/permissions/sync` - Sincronizar permissões (substituir todas)

#### Validação do Sync
O endpoint de sync espera:
```json
{
  "permission_ids": [1, 2, 3, ...]
}
```

E retorna erro 422 se:
- `permission_ids` não for enviado
- `permission_ids` não for um array
- Algum ID de permissão não existir

## Verificações de Segurança

O backend implementa as seguintes verificações:
1. **Tenant Isolation**: Todos os perfis e permissões são verificados para garantir que pertencem ao mesmo tenant do usuário autenticado
2. **Validação de Permissões**: Verifica se todas as permissões existem antes de sincronizar
3. **Transações**: Usa transações do banco de dados para garantir consistência

## Próximos Passos

Caso ainda haja problemas:

1. Verificar se o usuário autenticado tem a permissão `users.index` para ver a lista de usuários
2. Verificar se o token JWT está sendo enviado corretamente nas requisições
3. Verificar se o tenant_id do usuário está correto
4. Verificar logs do backend em `storage/logs/laravel.log`

## Notas Importantes

- **Roles foram descontinuadas**: O sistema agora usa apenas Profiles (Perfis)
- **Slug em Profiles**: Não é mais necessário (era usado em Roles)
- **Permission Slug**: É usado para identificar permissões no código (ex: `users.index`, `clients.view`)
