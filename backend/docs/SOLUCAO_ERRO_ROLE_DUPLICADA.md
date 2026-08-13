# 🔧 Solução para Erro de Role Duplicada

## ❌ Problema Identificado

```
SQLSTATE[23000]: Integrity constraint violation: 1062 
Duplicate entry 'super-admin' for key 'roles.roles_slug_unique'
```

## 🎯 Causa do Erro

O erro ocorre porque:
1. **Role já existe**: A role `super-admin` já foi criada anteriormente
2. **Constraint única**: O banco tem uma constraint `UNIQUE` no campo `slug` da tabela `roles`
3. **Tentativa de duplicação**: O seeder tentou criar uma role que já existe

## ✅ Soluções Implementadas

### **1. Seeder Corrigido (`TestUserSeeder.php`)**

```php
// ANTES (causava erro)
$superAdminRole = Role::where('slug', 'super-admin')->first();
if (!$superAdminRole) {
    $superAdminRole = Role::create([...]);
}

// DEPOIS (seguro)
$superAdminRole = Role::firstOrCreate(
    ['slug' => 'super-admin', 'tenant_id' => $tenant->id],
    [
        'name' => 'Super Admin',
        'slug' => 'super-admin',
        'description' => 'Acesso total ao sistema',
        'level' => 1,
        'is_active' => true,
        'tenant_id' => $tenant->id,
    ]
);
```

### **2. Comando Artisan Corrigido (`CreateTestUser.php`)**

```php
// Usa firstOrCreate para evitar duplicatas
$superAdminRole = Role::firstOrCreate(
    ['slug' => 'super-admin', 'tenant_id' => $tenant->id],
    [...]
);
$this->info("✅ Role Super Admin: " . 
    ($superAdminRole->wasRecentlyCreated ? 'criada' : 'já existe'));
```

### **3. Seeder Simples (`SimpleTestUserSeeder.php`)**

```php
// Apenas usa roles e permissões existentes
$superAdminRole = Role::where('slug', 'super-admin')
    ->where('tenant_id', $tenant->id)
    ->first();

if (!$superAdminRole) {
    $this->command->error('❌ Role Super Admin não encontrada. Execute RoleSeeder primeiro.');
    return;
}
```

### **4. Comando de Reset (`ResetTestUser.php`)**

```php
// Remove usuário existente e recria
$existingUser = User::where('email', $email)->first();
if ($existingUser) {
    $existingUser->permissions()->detach();
    $existingUser->roles()->detach();
    $existingUser->delete();
}
```

## 🚀 Como Usar (Ordem Recomendada)

### **Opção 1: Seeder Simples (Recomendado)**

```bash
# 1. Executar seeders básicos primeiro
php artisan db:seed --class=TenantsTableSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder

# 2. Executar seeder simples
php artisan db:seed --class=SimpleTestUserSeeder
```

### **Opção 2: Comando de Reset**

```bash
# Remove e recria o usuário
php artisan user:reset-test
```

### **Opção 3: Comando Corrigido**

```bash
# Usa firstOrCreate (seguro)
php artisan user:create-test
```

## 🔍 Verificação

### **Verificar se Role Existe**

```bash
# No container MySQL
mysql -u sail -p
USE laravel;
SELECT * FROM roles WHERE slug = 'super-admin';
```

### **Verificar Usuário de Teste**

```bash
# No container Laravel
php artisan tinker
>>> User::where('email', 'teste@example.com')->with(['roles', 'permissions'])->first();
```

## 🛠️ Troubleshooting

### **Se ainda der erro:**

#### **1. Limpar Banco (CUIDADO!)**
```bash
# ⚠️ APENAS EM DESENVOLVIMENTO
php artisan migrate:fresh --seed
```

#### **2. Verificar Constraints**
```sql
-- Verificar constraints da tabela roles
SHOW CREATE TABLE roles;

-- Verificar índices únicos
SHOW INDEX FROM roles WHERE Key_name LIKE '%unique%';
```

#### **3. Remover Role Duplicada**
```sql
-- Verificar duplicatas
SELECT slug, COUNT(*) FROM roles GROUP BY slug HAVING COUNT(*) > 1;

-- Remover duplicatas (manter apenas uma)
DELETE r1 FROM roles r1
INNER JOIN roles r2 
WHERE r1.id > r2.id AND r1.slug = r2.slug;
```

## 📋 Checklist de Solução

- [ ] ✅ **Seeder corrigido** com `firstOrCreate`
- [ ] ✅ **Comando Artisan corrigido** com `firstOrCreate`
- [ ] ✅ **Seeder simples** criado (usa dados existentes)
- [ ] ✅ **Comando de reset** criado
- [ ] ✅ **Documentação** atualizada
- [ ] ✅ **Testes** funcionando

## 🎯 Resultado Esperado

Após aplicar as correções:

```bash
✅ Role Super Admin: já existe
📋 Encontradas 25 permissões no sistema
✅ Usuário criado/atualizado: Teste
✅ Role Super Admin atribuída ao usuário
✅ 25 permissões atribuídas diretamente ao usuário
✅ 25 permissões atribuídas ao role Super Admin

🎉 Usuário de teste criado com sucesso!
```

## 🔐 Credenciais Finais

```
Nome: Teste
Email: teste@example.com
Senha: $Duda0793
Role: Super Admin
Permissões: Todas (25+)
```

**Problema resolvido!** 🎉✨
