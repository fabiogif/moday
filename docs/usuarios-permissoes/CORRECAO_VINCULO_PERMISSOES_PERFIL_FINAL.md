# Correção - Vínculo de Permissões a Perfis

## Problemas Identificados

### 1. Erro 404 "Perfil não encontrado"
**Causa**: O Route Model Binding do Laravel estava buscando o Profile pelo ID sem considerar o `tenant_id`. Quando o perfil pertencia a outro tenant (ou quando a verificação de tenant no controller falhava), retornava 404.

**Solução**: Adicionado route binding customizado no `RouteServiceProvider` que filtra automaticamente pelo `tenant_id` do usuário autenticado.

### 2. Permissões não sendo carregadas no modal
**Causa**: O hook `useAuthenticatedPermissions` retorna um objeto com estrutura `{permissions: [], pagination: {}}`, mas o código de filtragem não estava lidando corretamente com essa estrutura aninhada.

**Solução**: Melhorado o método `filterPermissions()` para extrair corretamente o array de permissões de diferentes estruturas de resposta da API.

## Arquivos Modificados

### Backend

#### `/backend/app/Providers/RouteServiceProvider.php`
- Adicionado route binding customizado para `profile`
- O binding agora filtra automaticamente pelo `tenant_id` do usuário autenticado
- Retorna 404 automaticamente se o perfil não existir ou não pertencer ao tenant do usuário

```php
Route::bind('profile', function ($value) {
    $user = auth('api')->user();
    
    if (!$user) {
        abort(401, 'Não autenticado');
    }
    
    $profile = \App\Models\Profile::where('id', $value)
        ->where('tenant_id', $user->tenant_id)
        ->first();
    
    if (!$profile) {
        abort(404, 'Perfil não encontrado');
    }
    
    return $profile;
});
```

### Frontend

#### `/frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx`
- Melhorado o método `filterPermissions()` para lidar com estruturas de resposta aninhadas
- Agora extrai corretamente o array de permissões de `{permissions: [], pagination: {}}`
- Mantida compatibilidade com arrays diretos e outras estruturas de resposta

## Funcionalidades Corrigidas

✅ Vinculação de permissões a perfis agora funciona corretamente
✅ Modal de "Vincular Permissões" carrega e exibe todas as permissões disponíveis
✅ Permissões são agrupadas por módulo para melhor visualização
✅ Permissões já vinculadas ao perfil são pré-selecionadas ao abrir o modal
✅ Segurança: Perfis são automaticamente filtrados por tenant_id

## Estrutura User -> Profile -> Permissions

A aplicação usa a estrutura recomendada:
- **User** → vinculado a um ou mais **Profiles**
- **Profile** → contém várias **Permissions**
- **Permission** → define ações específicas no sistema

Essa estrutura permite:
- Gerenciamento centralizado de permissões por perfil
- Múltiplos perfis por usuário
- Reutilização de perfis entre diferentes usuários
- Controle granular de acesso

## Próximos Passos

- Implementar interface de gerenciamento de usuários (se ainda não existir)
- Adicionar funcionalidade de vincular perfis a usuários
- Implementar sistema de ACL usando as permissões dos perfis vinculados
