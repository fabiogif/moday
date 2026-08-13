# Migração: User -> Profile -> Permissions

## Decisão

**USAR APENAS PROFILES** - Remover Roles e simplificar o sistema de controle de acesso (ACL).

## Estrutura Final

```
User -> Profiles -> Permissions
  └─> Permissions (diretas, opcional para casos excepcionais)
```

## O Que Foi Implementado

### 1. Backend - User Model Atualizado

**Arquivo:** `app/Models/User.php`

#### Novos Métodos (Profiles):
- ✅ `hasProfile(string $profileSlug)` - Verifica se usuário tem um perfil
- ✅ `hasAnyProfile(array $profiles)` - Verifica se tem qualquer perfil da lista
- ✅ `hasAllProfiles(array $profiles)` - Verifica se tem todos os perfis
- ✅ `assignProfile(Profile $profile)` - Atribui perfil ao usuário
- ✅ `removeProfile(Profile $profile)` - Remove perfil do usuário
- ✅ `syncProfiles(array $profiles)` - Sincroniza perfis do usuário

#### Métodos Atualizados:
- ✅ `hasPermissionTo()` - Agora verifica permissões via Profiles (não mais Roles)
- ✅ `hasAnyPermission()` - Verifica via Profiles
- ✅ `getAllPermissions()` - Retorna permissões diretas + via Profiles
- ✅ `isSuperAdmin()` - Usa `hasProfile('super-admin')`
- ✅ `isAdmin()` - Usa `hasProfile('admin')`
- ✅ `isManager()` - Usa `hasProfile('manager')`

#### Métodos Deprecated (Compatibilidade):
- ⚠️ `hasRole()` - Ainda funciona (fallback para `hasProfile()`)
- ⚠️ `hasAnyRole()` - Ainda funciona (fallback para `hasAnyProfile()`)
- ⚠️ `hasAllRoles()` - Ainda funciona (fallback para `hasAllProfiles()`)
- ⚠️ `assignRole()` - Deprecated (usar `assignProfile()`)
- ⚠️ `removeRole()` - Deprecated (usar `removeProfile()`)
- ⚠️ `syncRoles()` - Deprecated (usar `syncProfiles()`)

### 2. Script de Migração SQL

**Arquivo:** `backend/database/migrations_manual/migrate_roles_to_profiles.sql`

O script executa:
1. Cria Profiles equivalentes aos Roles existentes
2. Migra permissões dos Roles para os Profiles
3. Migra associações de usuários
4. Verifica os dados migrados

### 3. Comando Artisan para Migração

**Arquivo:** `app/Console/Commands/MigrateRolesToProfiles.php`

**Uso:**
```bash
# Modo teste (não salva alterações)
php artisan migrate:roles-to-profiles --dry-run

# Executar migração real
php artisan migrate:roles-to-profiles
```

**Funcionalidades:**
- ✅ Migra todos os dados automaticamente
- ✅ Verifica dados duplicados
- ✅ Modo dry-run para testes
- ✅ Transação de banco de dados
- ✅ Relatório detalhado do processo
- ✅ Rollback automático em caso de erro

## Como Executar a Migração

### Pré-requisitos

1. ✅ Fazer backup completo do banco de dados
2. ✅ Confirmar que aplicação está em ambiente de desenvolvimento/teste
3. ✅ Verificar que todos os serviços estão rodando

### Passo 1: Executar em Modo Teste

```bash
cd backend
php artisan migrate:roles-to-profiles --dry-run
```

**O que observar:**
- Quantidade de Profiles que serão criados
- Quantidade de permissões que serão migradas
- Quantidade de usuários afetados
- Mensagens de erro ou avisos

### Passo 2: Executar Migração Real

```bash
php artisan migrate:roles-to-profiles
```

**O comando irá:**
1. Solicitar confirmação de backup
2. Criar Profiles a partir dos Roles
3. Migrar permissões
4. Migrar associações de usuários
5. Exibir relatório final

### Passo 3: Verificar os Dados

```bash
# No MySQL ou outro client
USE seu_banco;

-- Ver Profiles criados
SELECT * FROM profiles ORDER BY tenant_id, name;

-- Ver usuários com Profiles
SELECT 
    u.name, u.email, p.name as profile_name
FROM users u
INNER JOIN user_profiles up ON u.id = up.user_id
INNER JOIN profiles p ON up.profile_id = p.id
ORDER BY u.name;

-- Ver permissões dos Profiles
SELECT 
    p.name as profile_name, 
    COUNT(pp.permission_id) as total_permissions
FROM profiles p
LEFT JOIN permission_profile pp ON p.id = pp.profile_id
GROUP BY p.id
ORDER BY p.name;
```

### Passo 4: Testar a Aplicação

1. **Login**: Fazer login com diferentes usuários
2. **Permissões**: Testar acesso a páginas protegidas
3. **ACL**: Verificar se `isAdmin()`, `isSuperAdmin()` funcionam
4. **Frontend**: Testar página de usuários e vinculação de perfis

### Passo 5: Limpar (Após Confirmação)

**ATENÇÃO:** Execute apenas após confirmar que tudo funciona!

```bash
# Comentar rotas de Roles em routes/api.php
# Ver seção "Limpeza" abaixo
```

## Compatibilidade com Código Existente

### ✅ Código que continua funcionando:

```php
// Estes métodos ainda funcionam (fazem fallback para Profiles)
if ($user->hasRole('admin')) { ... }           // ✅ Funciona
if ($user->isAdmin()) { ... }                  // ✅ Funciona
if ($user->isSuperAdmin()) { ... }             // ✅ Funciona
if ($user->hasPermissionTo('users.index')) { ... } // ✅ Funciona
```

### ✨ Novo código recomendado:

```php
// Use os novos métodos para maior clareza
if ($user->hasProfile('admin')) { ... }        // ✨ Recomendado
if ($user->hasAnyProfile(['admin', 'manager'])) { ... }
$user->assignProfile($adminProfile);
$user->syncProfiles([1, 2, 3]);
```

## Estrutura de Dados

### Antes (Complexo):
```
User -> Roles -> Permissions
User -> Permissions (diretas)
User -> Profiles -> Permissions

3 formas diferentes de atribuir permissões ❌
```

### Depois (Simples):
```
User -> Profiles -> Permissions
User -> Permissions (diretas, opcional)

1 forma principal + 1 excepcional ✅
```

## Limpeza (Opcional)

### Após confirmar que tudo funciona:

#### 1. Comentar Rotas de Roles

**Arquivo:** `routes/api.php`

```php
// Comentar toda a seção de Roles:
/*
Route::prefix('role')->group(function () {
    Route::get('/', [RoleApiController::class, 'index']);
    // ... todas as rotas de roles
});
*/
```

#### 2. (Opcional) Remover Dados de Roles do Banco

```sql
-- ATENÇÃO: Execute apenas se tiver CERTEZA absoluta!

-- Remover associações
DELETE FROM role_user;
DELETE FROM role_permissions;

-- Remover roles
DELETE FROM roles;
```

#### 3. (Futuro) Remover Código de Roles

**Não é urgente, pode ser feito gradualmente:**
- Remover `app/Models/Role.php`
- Remover `app/Http/Controllers/Api/RoleApiController.php`
- Remover middleware `CheckRole.php`
- Remover migrações de roles

## Benefícios da Migração

### ✅ Simplicidade
- 1 sistema de controle de acesso em vez de 3
- Menos tabelas para consultar
- Código mais fácil de entender

### ✅ Performance
- Menos JOINs nas queries
- Cache mais eficiente
- Menos overhead de verificações

### ✅ Manutenibilidade
- Um único lugar para gerenciar permissões
- Menos confusão entre Roles e Profiles
- Código mais limpo

### ✅ Consistência
- Frontend e Backend usam o mesmo conceito
- Interface amigável já implementada
- Multi-tenant nativo

### ✅ User Experience
- "Perfil" é mais intuitivo que "Role"
- Interface visual já existe (página de usuários)
- Fácil de explicar para usuários finais

## Troubleshooting

### Problema: "Profile não encontrado após migração"

**Solução:**
```bash
# Verificar se migração foi executada
php artisan tinker
>>> Profile::count();
>>> User::first()->profiles;
```

### Problema: "Permissões não funcionam"

**Solução:**
```bash
# Verificar permissões do usuário
php artisan tinker
>>> $user = User::where('email', 'teste@example.com')->first();
>>> $user->getAllPermissions();
>>> $user->profiles;
```

### Problema: "Erro ao executar comando de migração"

**Solução:**
1. Verificar se banco de dados está acessível
2. Verificar se tabelas `profiles` e `permission_profile` existem
3. Executar em modo `--dry-run` primeiro
4. Verificar logs em `storage/logs/laravel.log`

## Próximos Passos

1. ✅ Executar migração em ambiente de desenvolvimento
2. ✅ Testar completamente a aplicação
3. ✅ Verificar logs de erro
4. ✅ Confirmar que todos os usuários conseguem fazer login
5. ✅ Confirmar que permissões funcionam corretamente
6. ⏳ Executar em staging
7. ⏳ Executar em produção
8. ⏳ Remover código antigo de Roles (gradualmente)

## Rollback (Se Necessário)

Se algo der errado, você pode reverter:

```sql
-- Restaurar backup do banco de dados
-- OU manualmente:

-- Remover Profiles criados pela migração
DELETE p FROM profiles p
INNER JOIN roles r ON p.slug = r.slug AND p.tenant_id = r.tenant_id;

-- Remover associações criadas
DELETE up FROM user_profiles up
INNER JOIN profiles p ON up.profile_id = p.id
INNER JOIN roles r ON p.slug = r.slug AND p.tenant_id = r.tenant_id;
```

## Suporte

Em caso de dúvidas ou problemas:
1. Verificar logs: `storage/logs/laravel.log`
2. Executar comando em modo `--dry-run`
3. Consultar esta documentação
4. Verificar o código do User model atualizado
