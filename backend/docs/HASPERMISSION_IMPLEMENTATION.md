# Sistema de Permissões - hasPermission

## Visão Geral

O sistema `hasPermission` é um componente central do sistema de controle de acesso (ACL) implementado no projeto Laravel. Ele permite verificar se um usuário possui permissões específicas para executar determinadas ações na aplicação.

## Arquitetura do Sistema

### 1. Estrutura de Componentes

```
app/
├── Models/
│   ├── User.php (usa UserACLTrait)
│   ├── Role.php
│   ├── Permission.php
│   └── Traits/
│       └── UserACLTrait.php (implementação principal)
├── Http/
│   ├── Controllers/
│   │   └── BaseController.php (métodos auxiliares)
│   └── Middleware/
│       └── PermissionMiddleware.php
├── Services/
│   └── AclService.php
└── Repositories/
    └── UserRepository.php (métodos de cache)
```

### 2. Relacionamentos do Banco de Dados

```sql
users
├── role_user (many-to-many)
├── roles
│   └── permission_role (many-to-many)
│       └── permissions
└── profile_user (many-to-many)
    └── profiles
        └── permission_profile (many-to-many)
            └── permissions
```

## Implementação Técnica

### 1. Trait UserACLTrait

**Localização**: `app/Models/Traits/UserACLTrait.php`

```php
public function hasPermission(string $permissionName): bool
{
    // 1. Verificação de Admin
    if ($this->isAdmin()) {
        return true;
    }

    // 2. Logging para debug
    Log::debug('Verificando permissão', [
        'user_id' => $this->id,
        'permission' => $permissionName
    ]);

    // 3. Método de verificação configurável
    $checkMethod = config('acl.check_method', 'both');

    switch ($checkMethod) {
        case 'roles':
            return $this->hasPermissionThroughRoles($permissionName);
        case 'permissions':
            return in_array($permissionName, $this->getPermissionsList());
        default: // 'both'
            return in_array($permissionName, $this->getPermissionsList()) ||
                   $this->hasPermissionThroughRoles($permissionName);
    }
}
```

### 2. Estratégias de Verificação

#### A. Verificação por Cache (Recomendada)

```php
public function getPermissionsList(): array
{
    if ($this->isAdmin()) {
        return $this->getAllPermissionsFromDatabase();
    }

    // Cache com TTL configurável
    if (config('acl.cache.enabled', false)) {
        $cacheKey = "user_permissions_{$this->id}";
        $cacheTtl = config('acl.cache.ttl', 60 * 24); // 24 horas

        return Cache::remember($cacheKey, $cacheTtl, function () {
            return $this->permissionsRole();
        });
    }

    return $this->permissionsRole();
}
```

#### B. Verificação por Relacionamentos

```php
protected function hasPermissionThroughRoles(string $permissionSlug): bool
{
    return $this->roles()->whereHas('permissions', function ($q) use ($permissionSlug) {
        $q->where('slug', $permissionSlug);
    })->exists();
}
```

### 3. Sistema de Cache Avançado

**UserRepository.php**:

```php
public function hasPermission(int $userId, string $permissionSlug): bool
{
    $cacheKey = "user_permissions:{$userId}:has:{$permissionSlug}";

    return $this->cacheService->remember($cacheKey, function () use ($userId, $permissionSlug) {
        return $this->entity->whereId($userId)
            ->whereHas('roles.permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->orWhereHas('profiles.permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }, 3600); // Cache por 1 hora
}
```

## Configuração

### 1. Arquivo de Configuração ACL

**config/acl.php**:

```php
return [
    'cache' => [
        'enabled' => env('ACL_CACHE_ENABLED', true),
        'ttl' => env('ACL_CACHE_TTL', 86400), // 24 horas
    ],
    'check_method' => env('ACL_CHECK_METHOD', 'both'), // 'roles', 'permissions', 'both'
    'admin_emails' => [
        'admin@example.com',
        'superuser@example.com',
    ],
];
```

### 2. Variáveis de Ambiente

```env
ACL_CACHE_ENABLED=true
ACL_CACHE_TTL=86400
ACL_CHECK_METHOD=both
```

## Uso Prático

### 1. No Backend (Controllers)

```php
class UserController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        // Verificação direta
        if (!$request->user()->hasPermission('users.index')) {
            abort(403, 'Ação não autorizada.');
        }

        // Ou usando método auxiliar do BaseController
        $this->authorizeOrFail('users.index');

        // Lógica do controller...
    }
}
```

### 2. No Middleware

```php
class PermissionMiddleware
{
    public function handle($request, Closure $next, $permission)
    {
        if (!$request->user()->hasPermission($permission)) {
            abort(403, 'Ação não autorizada.');
        }

        return $next($request);
    }
}
```

### 3. Nas Rotas

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'permission:users.index'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

### 4. No Frontend (React)

```typescript
// hooks/usePermissions.ts
export const usePermissions = () => {
    const hasPermission = (permission: string): boolean => {
        if (!isAuthenticated || !user) return false;
        return permissions.includes(permission);
    };

    return { hasPermission };
};

// Componente
const UserList = () => {
    const { hasPermission } = usePermissions();

    return (
        <div>
            {hasPermission("users.create") && <button>Criar Usuário</button>}
        </div>
    );
};
```

## Funcionalidades Avançadas

### 1. Verificação de Admin

```php
public function isAdmin(): bool
{
    $adminEmails = config('acl.admin_emails', []);
    return in_array($this->email, $adminEmails);
}
```

### 2. Limpeza de Cache

```php
public function clearPermissionsCache(): void
{
    Cache::forget("user_permissions_{$this->id}");
}
```

### 3. Verificação de Roles

```php
public function hasRole(string $roleName): bool
{
    return $this->roles()->where('name', $roleName)->exists();
}
```

### 4. Métodos Auxiliares no BaseController

```php
protected function checkPermission(string $permission, $resource = null): bool
{
    $user = request()->user();
    return $user ? $user->hasPermission($permission) : false;
}

protected function authorizeOrFail(string $permission, $resource = null): void
{
    if (!$this->checkPermission($permission, $resource)) {
        abort(403, 'Ação não autorizada.');
    }
}
```

## Performance e Otimização

### 1. Estratégias de Cache

-   **Cache de Lista Completa**: Armazena todas as permissões do usuário
-   **Cache por Permissão**: Cache individual para cada verificação
-   **TTL Configurável**: Tempo de vida do cache ajustável
-   **Invalidação Automática**: Limpeza automática em mudanças de permissões

### 2. Métodos de Verificação

| Método        | Performance | Precisão | Uso Recomendado         |
| ------------- | ----------- | -------- | ----------------------- |
| `roles`       | Média       | Alta     | Verificações pontuais   |
| `permissions` | Alta        | Alta     | Verificações frequentes |
| `both`        | Baixa       | Máxima   | Desenvolvimento/Testes  |

### 3. Logging e Monitoramento

```php
// Log de verificações (debug)
Log::debug('Verificando permissão', [
    'user_id' => $this->id,
    'permission' => $permissionName
]);

// Log de requisições lentas
if ($executionTime * 1000 > $slowThreshold) {
    Log::warning('Slow request detected', $performanceData);
}
```

## Exemplos de Uso

### 1. Verificações Simples

```php
// Verificar permissão específica
if ($user->hasPermission('users.create')) {
    // Usuário pode criar usuários
}

// Verificar múltiplas permissões
if ($user->hasPermission('users.edit') || $user->hasPermission('users.admin')) {
    // Usuário pode editar ou é admin
}
```

### 2. Verificações Condicionais

```php
// Verificação com contexto
public function updateUser(Request $request, User $user)
{
    // Usuário pode editar qualquer usuário ou editar a si mesmo
    if ($request->user()->hasPermission('users.edit') ||
        $request->user()->id === $user->id) {
        // Permitir edição
    }
}
```

### 3. Verificações em Blade Templates

```php
@if(auth()->user()->hasPermission('users.delete'))
    <button class="btn btn-danger">Excluir</button>
@endif
```

## Troubleshooting

### 1. Problemas Comuns

**Cache não atualizando**:

```php
// Limpar cache manualmente
$user->clearPermissionsCache();
```

**Permissões não funcionando**:

```php
// Verificar configuração
dd(config('acl.check_method'));
dd(config('acl.cache.enabled'));
```

**Performance lenta**:

```php
// Usar método 'permissions' para cache
config(['acl.check_method' => 'permissions']);
```

### 2. Debug e Logs

```php
// Habilitar logs detalhados
Log::debug('Permission check', [
    'user_id' => $user->id,
    'permission' => $permission,
    'result' => $user->hasPermission($permission),
    'permissions_list' => $user->getPermissionsList()
]);
```

## Conclusão

O sistema `hasPermission` oferece uma solução robusta e flexível para controle de acesso, com:

-   ✅ **Performance otimizada** com sistema de cache
-   ✅ **Flexibilidade** com múltiplos métodos de verificação
-   ✅ **Segurança** com verificação através de roles e permissões
-   ✅ **Escalabilidade** preparada para grandes volumes
-   ✅ **Debugging** com logs detalhados
-   ✅ **Integração** com frontend e backend

O sistema está preparado para crescer com a aplicação, mantendo performance e segurança em todos os níveis.
