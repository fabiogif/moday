# Correção: Erro ao Vincular Usuário ao Perfil

## Problema

Ao tentar vincular um perfil a um usuário, a API retornava:

```json
{
    "success": true,
    "data": null,
    "message": "Usuário não encontrado"
}
```

## Causa Raiz

O problema estava no `UserApiController`. Os métodos do controller estavam usando **Route Model Binding** do Laravel com o parâmetro `User $user`, mas as rotas definidas em `routes/api.php` usavam `{id}` ao invés de `{user}`.

### Exemplo do Problema:

**Rota definida:**
```php
Route::post('/{id}/assign-profile', [UserApiController::class, 'assignProfile'])
```

**Método do controller:**
```php
public function assignProfile(UserAssignProfileRequest $request, User $user)
{
    // O Laravel tentava fazer binding de {id} para User $user
    // Como o parâmetro não se chamava {user}, o binding falhava
    // Resultado: $user era NULL
    if ($user->tenant_id !== auth()->user()->tenant_id) {
        // Erro: "Usuário não encontrado"
    }
}
```

## Solução Implementada

Alteramos todos os métodos do `UserApiController` que dependiam de ID para receberem `$id` como parâmetro e buscarem o usuário manualmente usando `User::find($id)`.

### Métodos Corrigidos:

1. **show($id)** - Visualizar detalhes do usuário
2. **update($request, $id)** - Atualizar usuário
3. **destroy($id)** - Excluir usuário
4. **assignProfile($request, $id)** - Vincular perfil ao usuário
5. **changePassword($request, $id)** - Alterar senha do usuário
6. **getUserPermissions($id)** - Obter permissões do usuário

### Exemplo da Correção:

**ANTES:**
```php
public function assignProfile(UserAssignProfileRequest $request, User $user): JsonResponse
{
    try {
        // $user era NULL porque o binding não funcionava
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
        }
        // ...
    }
}
```

**DEPOIS:**
```php
public function assignProfile(UserAssignProfileRequest $request, $id): JsonResponse
{
    try {
        $user = User::find($id);
        
        if (!$user) {
            return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
        }
        
        // Verificar se o usuário pertence ao mesmo tenant
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
        }
        
        // Restante do código...
    }
}
```

## Arquivo Modificado

- `backend/app/Http/Controllers/Api/UserApiController.php`

## Funcionalidades Corrigidas

✅ Vincular perfil ao usuário  
✅ Alterar senha do usuário  
✅ Visualizar detalhes do usuário  
✅ Atualizar dados do usuário  
✅ Excluir usuário  
✅ Obter permissões do usuário  

## Testando a Correção

1. Acesse a página de usuários: `http://localhost:3001/users`
2. Clique no menu de ações (três pontos) de um usuário
3. Selecione "Vincular Perfil"
4. Escolha um perfil da lista
5. Clique em "Vincular Perfil"
6. Deve exibir mensagem de sucesso: "Perfil vinculado com sucesso"

Teste também as outras funcionalidades:
- Alterar senha
- Excluir usuário
- Editar usuário

## Alternativa: Route Model Binding

Se preferir usar o padrão Route Model Binding do Laravel (mais elegante), você pode alterar as rotas em `routes/api.php`:

### Alterar de {id} para {user}:

```php
// ANTES
Route::get('/{id}', [UserApiController::class, 'show']);
Route::put('/{id}', [UserApiController::class, 'update']);
Route::delete('/{id}', [UserApiController::class, 'destroy']);
Route::post('/{id}/assign-profile', [UserApiController::class, 'assignProfile']);
Route::put('/{id}/change-password', [UserApiController::class, 'changePassword']);
Route::get('/{id}/permissions', [UserApiController::class, 'getUserPermissions']);

// DEPOIS
Route::get('/{user}', [UserApiController::class, 'show']);
Route::put('/{user}', [UserApiController::class, 'update']);
Route::delete('/{user}', [UserApiController::class, 'destroy']);
Route::post('/{user}/assign-profile', [UserApiController::class, 'assignProfile']);
Route::put('/{user}/change-password', [UserApiController::class, 'changePassword']);
Route::get('/{user}/permissions', [UserApiController::class, 'getUserPermissions']);
```

E reverter os métodos do controller para usar `User $user` novamente.

## Observações

- A solução implementada (busca manual com `find($id)`) é mais explícita e facilita o debug
- O Route Model Binding é mais elegante mas pode causar confusão se o nome do parâmetro não for consistente
- Ambas as abordagens são válidas e funcionam corretamente
