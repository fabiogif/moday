# Correção: Vínculo de Permissões ao Perfil

## Problemas Identificados e Soluções

### 1. Erro 404 "Perfil não encontrado" ao vincular permissões

**Problema**: O erro ocorria porque havia inconsistência entre os parâmetros de rota e os parâmetros do controller.

**Erro Original**:
```
ApiClient: Erro HTTP 404 : Perfil não encontrado
PUT http://localhost/api/profiles/2/permissions/sync 404 (Not Found)
```

**Causa Raiz**: As rotas usavam `{id}` como parâmetro, mas o controller esperava model binding com `Profile $profile`.

**Solução**: Alteradas todas as rotas de profiles para usar `{profile}` em vez de `{id}`:

```php
// backend/routes/api.php
Route::prefix('profiles')->group(function () {
    Route::get('/{profile}', [ProfileApiController::class, 'show']);
    Route::put('/{profile}', [ProfileApiController::class, 'update']);
    Route::delete('/{profile}', [ProfileApiController::class, 'destroy']);
    
    // Gerenciar permissões do perfil
    Route::get('/{profile}/permissions', [PermissionProfileApiController::class, 'getProfilePermissions']);
    Route::put('/{profile}/permissions/sync', [PermissionProfileApiController::class, 'syncPermissionsForProfile']);
    Route::delete('/{profile}/permissions/{permission}', [PermissionProfileApiController::class, 'detachPermissionFromProfile']);
    // ... outras rotas
});
```

### 2. Permissões não aparecem no modal "Vincular Permissões"

**Problema**: O modal mostrava "Nenhuma permissão disponível" mesmo quando as permissões eram carregadas corretamente do backend.

**Causa**: A função `filterPermissions()` não estava conseguindo extrair o array de permissões do objeto retornado pela API.

**Solução**: Melhorada a lógica de extração de dados em `assign-permissions-dialog.tsx`:

```typescript
const filterPermissions = () => {
  let permissionsArray: Permission[] = []
  
  if (!allPermissions) {
    return []
  }
  
  // Verificar se allPermissions é um array diretamente
  if (Array.isArray(allPermissions)) {
    permissionsArray = allPermissions
  } 
  // Verificar se allPermissions é um objeto com propriedade 'permissions'
  else if (typeof allPermissions === 'object') {
    if ('permissions' in allPermissions && Array.isArray((allPermissions as any).permissions)) {
      permissionsArray = (allPermissions as any).permissions
    } else if ('data' in allPermissions && Array.isArray((allPermissions as any).data)) {
      permissionsArray = (allPermissions as any).data
    }
  }
  
  // ... resto da lógica de filtro
}
```

### 3. Logs de Debug Adicionados

Para facilitar o diagnóstico de problemas futuros, foram adicionados logs detalhados:

```typescript
console.log('filterPermissions - allPermissions raw:', allPermissions)
console.log('filterPermissions - typeof allPermissions:', typeof allPermissions)
console.log('Permissoes carregadas:', permissionsArray)
```

## Estrutura de Dados Esperada

### Resposta da API `/api/permissions`:
```json
{
  "success": true,
  "data": {
    "permissions": [
      {
        "id": 1,
        "name": "Visualizar Clientes",
        "slug": "clients.view",
        "description": "Visualizar lista de clientes",
        "module": "clients",
        "action": "view"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 15
    }
  }
}
```

### Resposta da API `/api/profiles/{profile}/permissions/sync`:
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Gerente",
    "permissions": [...]
  },
  "message": "Permissões do perfil sincronizadas com sucesso"
}
```

## Benefícios do Model Binding

Com a correção do model binding, o Laravel agora:

1. **Busca automaticamente** o Profile pelo ID passado na URL
2. **Valida** se o registro existe (retorna 404 automático se não existir)
3. **Injeta** a instância do modelo diretamente no método do controller
4. **Verifica tenant** automaticamente no controller
5. **Melhora a performance** com cache de binding

## Teste

Para testar o vínculo de permissões:

1. Acessar a página de Perfis (`/profiles`)
2. Clicar em "Vincular Permissões" em um perfil
3. Verificar se as permissões são exibidas corretamente
4. Selecionar as permissões desejadas
5. Clicar em "Salvar Permissões"
6. Verificar se a mensagem de sucesso é exibida
7. Recarregar a página e verificar se as permissões estão vinculadas

## Arquivos Modificados

### Backend
- `backend/routes/api.php` - Atualização das rotas de profiles para usar model binding

### Frontend
- `frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx` - Melhoria na extração de dados de permissões e adição de logs de debug

## Próximos Passos

Após confirmar que as permissões estão sendo vinculadas corretamente:

1. Remover os logs de debug adicionados (console.log)
2. Testar a funcionalidade de desvincular permissões
3. Testar a funcionalidade de vincular perfis a usuários
4. Verificar se as permissões são respeitadas no controle de acesso
