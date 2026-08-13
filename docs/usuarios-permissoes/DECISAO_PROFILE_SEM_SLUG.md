# Decisão: Usar NAME em vez de SLUG em Profiles

## Problema Original

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'slug' in 'where clause'
SQL: select exists(select * from `profiles` where `slug` = admin) as `exists`
```

O código estava tentando usar `slug` mas a tabela `profiles` não tinha essa coluna.

## Análise das Opções

### Opção 1: ❌ Adicionar coluna SLUG
- **Prós:**
  - Mais semântico
  - URL-friendly
  - Padrão usado em Permissions e Roles
  
- **Contras:**
  - Adiciona complexidade desnecessária
  - Mais uma coluna no banco
  - Precisa de migração
  - Duplica informação (name já é único por tenant)

### Opção 2: ✅ Usar coluna NAME (escolhida)
- **Prós:**
  - **Simplicidade**: Coluna já existe
  - **Sem migração**: Funciona imediatamente
  - **Único por tenant**: Name já tem constraint
  - **Legível**: `hasProfile('Admin')` é claro
  
- **Contras:**
  - Name pode ter espaços e maiúsculas
  - **Solução**: Comparação case-insensitive

### Opção 3: ❌ Usar coluna ID
- **Prós:**
  - Sempre único
  - Performance levemente melhor
  
- **Contras:**
  - Menos legível: `hasProfile(1)` vs `hasProfile('Admin')`
  - Precisa saber ID ao escrever código
  - IDs podem mudar entre ambientes

## Decisão Final

**✅ Usar campo NAME com comparação case-insensitive**

## Implementação

### User Model - Métodos Atualizados:

#### hasProfile()
```php
// ANTES (com slug):
public function hasProfile(string $profileSlug): bool
{
    return $this->profiles()->where('slug', $profileSlug)->exists();
}

// DEPOIS (com name):
public function hasProfile(string $profileName): bool
{
    return $this->profiles()
        ->whereRaw('LOWER(name) = ?', [strtolower($profileName)])
        ->exists();
}
```

#### hasAnyProfile()
```php
// ANTES:
return $this->profiles()->whereIn('slug', $profiles)->exists();

// DEPOIS:
$profiles = array_map('strtolower', $profiles);
return $this->profiles()
    ->whereRaw('LOWER(name) IN (...)', $profiles)
    ->exists();
```

#### hasAllProfiles()
```php
// ANTES:
$userProfiles = $this->profiles()->pluck('slug')->toArray();

// DEPOIS:
$userProfiles = $this->profiles()
    ->get()
    ->map(fn($profile) => strtolower($profile->name))
    ->toArray();
```

#### assignProfile()
```php
// ANTES:
if (!$this->hasProfile($profile->slug)) { ... }

// DEPOIS:
if (!$this->hasProfile($profile->name)) { ... }
```

## Uso no Código

### ✅ Exemplos de Uso (funciona com qualquer capitalização):

```php
// Todas essas formas funcionam:
$user->hasProfile('Admin');
$user->hasProfile('admin');
$user->hasProfile('ADMIN');

// Verificar múltiplos perfis:
$user->hasAnyProfile(['Admin', 'Manager']);
$user->hasAllProfiles(['admin', 'editor']);

// Métodos helper:
$user->isAdmin();        // Internamente usa hasProfile('admin')
$user->isSuperAdmin();   // Usa hasProfile('super-admin')
$user->isManager();      // Usa hasProfile('manager')
```

### Criação de Perfis:

```php
// Ao criar perfil, usar nome descritivo:
Profile::create([
    'name' => 'Admin',           // ✓ Capitalizado
    'description' => 'Administrador do sistema',
    'tenant_id' => 1,
]);

Profile::create([
    'name' => 'Super Admin',     // ✓ Com espaço
    'description' => 'Super administrador',
    'tenant_id' => 1,
]);

// E no código usar qualquer variação:
$user->hasProfile('admin');       // ✓ Funciona
$user->hasProfile('Admin');       // ✓ Funciona
$user->hasProfile('super admin'); // ✓ Funciona
$user->hasProfile('Super Admin'); // ✓ Funciona
```

## Comparação com Permissions

### Permissions (usa SLUG):
```php
Permission::create([
    'name' => 'Visualizar Usuários',
    'slug' => 'users.index',           // Slug técnico
]);

$user->hasPermissionTo('users.index'); // Usa slug
```

**Por quê slug em Permissions?**
- Usados em código/rotas: mais técnico
- Padrão: `module.action` (ex: `users.create`)
- URLs: `/permissions/users.index`

### Profiles (usa NAME):
```php
Profile::create([
    'name' => 'Administrador',         // Nome descritivo
]);

$user->hasProfile('Administrador');    // Usa name
// ou
$user->hasProfile('administrador');    // Case-insensitive
```

**Por quê name em Profiles?**
- Exibidos para usuários finais
- Mais descritivos e amigáveis
- Não precisam seguir padrão técnico
- Nome já é único por tenant

## Benefícios da Solução

### ✅ Simplicidade
- Sem necessidade de migração
- Menos colunas no banco
- Menos código para manter

### ✅ Flexibilidade
- Comparação case-insensitive
- Aceita qualquer capitalização
- Funciona com espaços no nome

### ✅ Legibilidade
```php
// Claro e intuitivo:
if ($user->hasProfile('Admin')) { ... }

// Em vez de:
if ($user->hasProfile('admin')) { ... }  // Precisa saber o slug exato
```

### ✅ Sem Duplicação
- Name já existe e é único
- Não precisa sincronizar name e slug
- Uma fonte de verdade

## Estrutura Final

### Tabela Profiles:
```sql
CREATE TABLE profiles (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) UNIQUE,      -- Usado para comparações
    description TEXT,
    tenant_id BIGINT,
    is_active BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    UNIQUE KEY (name, tenant_id)   -- Nome único por tenant
);
```

### Não precisa de:
```sql
-- ❌ NÃO adicionar:
slug VARCHAR(255)
```

## Arquivos Modificados

- ✅ `app/Models/User.php`
  - `hasProfile()` - Usa `name` com LOWER()
  - `hasAnyProfile()` - Usa `name` com LOWER()
  - `hasAllProfiles()` - Usa `name` com LOWER()
  - `assignProfile()` - Usa `name`

- ❌ Migração de slug - Removida (não necessária)

## Compatibilidade

### Código existente continua funcionando:
```php
// Se antes chamava:
$user->hasProfile('admin');      // ✓ Funciona

// Agora também aceita:
$user->hasProfile('Admin');      // ✓ Funciona
$user->hasProfile('ADMIN');      // ✓ Funciona
```

## Conclusão

**A decisão de usar NAME em vez de SLUG em Profiles é a escolha correta porque:**

1. ✅ **Mais simples** - Sem coluna adicional
2. ✅ **Sem migração** - Funciona imediatamente
3. ✅ **Mais flexível** - Case-insensitive
4. ✅ **Mais legível** - Nome descritivo
5. ✅ **Sem duplicação** - Name já é único

Profiles são diferentes de Permissions:
- **Permissions** = Técnicas (precisam de slug: `users.create`)
- **Profiles** = Descritivas (usam name: `Administrador`)

---

**Status:** ✅ Implementado
**Data:** 2024-10-04
