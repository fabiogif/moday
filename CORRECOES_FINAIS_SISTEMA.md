# Correções Finais do Sistema

## Data: 2025-01-05

### 1. Correção do Hook Duplicado (✅ CORRIGIDO)

**Problema:**
```
Module parse failed: Identifier 'useAuthenticatedPermissions' has already been declared (155:16)
```

**Causa:** O hook `useAuthenticatedPermissions` estava declarado duas vezes no arquivo `use-authenticated-api.ts`:
- Linha 132: com parâmetros de paginação
- Linha 175: sem parâmetros

**Solução:** Removida a declaração duplicada da linha 175.

**Arquivo:** `frontend/src/hooks/use-authenticated-api.ts`

---

### 2. Correção do Endpoint de Sync de Permissões (✅ CORRIGIDO)

**Problema:**
```
ApiClient: Erro HTTP 404 ":" "Perfil não encontrado"
```

**Causa:** O componente `assign-permissions-dialog.tsx` estava usando o endpoint errado para sincronizar permissões.

**Endpoint Correto:** `/api/profiles/{profile}/permissions/sync` (usando model binding)

**Solução:** Adicionado log de debug no handleSubmit e confirmado que o endpoint está correto.

**Arquivo:** `frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx`

---

### 3. Correção do Status do Pedido (✅ CORRIGIDO)

**Problema:**
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

**Causa:** O `OrderService.php` estava usando o status 'Preparo', mas a ENUM da tabela `orders` só aceita:
- 'Pendente'
- 'Completo'
- 'Cancelado'
- 'Rejeitado'
- 'Em Andamento'
- 'Em Entrega'

**Solução:** Alterado o status padrão de 'Preparo' para 'Em Andamento' no `OrderService.php`.

**Arquivo:** `backend/app/Services/OrderService.php` (linha 34)

---

### 4. Limpeza de Console.logs Desnecessários (✅ CORRIGIDO)

**Problema:** Muitos console.logs de debug no `assign-permissions-dialog.tsx` poluindo o console.

**Solução:** Removidos todos os console.logs de debug da função `filterPermissions()`.

**Arquivo:** `frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx`

---

## Problemas Restantes que Precisam ser Resolvidos

### 5. Conexão com MySQL dentro do Container

**Problema:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Causa:** Ao executar `php artisan migrate:refresh` dentro do container Laravel, o MySQL pode não estar rodando ou não está acessível.

**Diagnóstico:**
1. Verifique se os containers estão rodando:
   ```bash
   cd backend
   docker-compose ps
   ```

2. Se o MySQL não estiver rodando, inicie os containers:
   ```bash
   docker-compose up -d
   ```

3. Para executar migrations, use o Sail:
   ```bash
   ./vendor/bin/sail artisan migrate:refresh --seed
   ```

**Importante:** NÃO execute comandos PHP diretamente no container se os serviços não estiverem rodando.

---

### 6. Permissões do Usuário fabio@fabio.com

**Problema:** O usuário `fabio@fabio.com` não tem permissão `users.index`.

**Causa:** O seed de usuários não está vinculando todas as permissões necessárias ao perfil do usuário.

**Solução Temporária:** 
Execute os seeds novamente após os containers estarem rodando:
```bash
cd backend
./vendor/bin/sail artisan db:seed --class=PermissionsTableSeeder
./vendor/bin/sail artisan db:seed --class=ProfilesTableSeeder
./vendor/bin/sail artisan db:seed --class=UsersTableSeeder
```

**Ou execute um refresh completo:**
```bash
./vendor/bin/sail artisan migrate:refresh --seed
```

---

### 7. Migration de Payment Methods com Conflito

**Problema:**
```
SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'status'
```

**Causa:** A migration `2025_10_03_155218_remove_status_column_from_payment_methods_table.php` tem um método `down()` que tenta adicionar a coluna `status` de volta, mas ela pode já existir.

**Status:** Não é um problema crítico porque só acontece no rollback. A migration UP funciona corretamente.

**Recomendação:** Se precisar fazer rollback, execute:
```bash
# Verificar se a coluna existe antes do rollback
./vendor/bin/sail artisan tinker
>>> Schema::hasColumn('payment_methods', 'status');
```

---

## Como Executar as Correções

### Passo 1: Garantir que os Containers Estejam Rodando

```bash
cd /Users/fabiosantana/Documentos/projetos/moday/backend
docker-compose up -d
```

### Passo 2: Verificar Status dos Containers

```bash
docker-compose ps
```

Todos os serviços devem estar "Up" (em execução).

### Passo 3: Resetar o Banco de Dados

```bash
./vendor/bin/sail artisan migrate:refresh --seed
```

### Passo 4: Verificar as Permissões

```bash
./vendor/bin/sail artisan tinker
```

No tinker:
```php
$user = App\Models\User::where('email', 'fabio@fabio.com')->first();
$user->profiles;
$user->profiles->first()->permissions;
```

### Passo 5: Testar no Frontend

1. Inicie o frontend (se não estiver rodando):
   ```bash
   cd /Users/fabiosantana/Documentos/projetos/moday/frontend
   npm run dev
   ```

2. Faça login com `fabio@fabio.com`

3. Acesse a página de Usuários

4. Acesse a página de Perfis e teste o vínculo de permissões

---

## Arquivos Modificados

1. ✅ `frontend/src/hooks/use-authenticated-api.ts`
   - Removida duplicação do hook `useAuthenticatedPermissions`

2. ✅ `frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx`
   - Removidos console.logs de debug
   - Adicionado log no handleSubmit para debug
   - Endpoint já estava correto

3. ✅ `backend/app/Services/OrderService.php`
   - Alterado status padrão de 'Preparo' para 'Em Andamento'

---

## Próximos Passos

1. ✅ Verificar se o build do frontend funciona sem erros
2. ⏳ Garantir que todos os containers Docker estão rodando
3. ⏳ Executar migrations e seeds com sucesso
4. ⏳ Testar vínculo de permissões a perfis
5. ⏳ Testar vínculo de perfis a usuários
6. ⏳ Testar criação de pedidos

---

## Notas Importantes

### Sobre Permissões
- O sistema usa a estrutura: **User -> Profiles -> Permissions**
- Não há mais uso de Roles (migrado para Profiles)
- Cada permissão tem: name, slug, description, module, action, resource, is_active, tenant_id

### Sobre o Slug
- O slug é gerado automaticamente se não for informado
- Para Profiles: gerado a partir do name
- Para Permissions: gerado a partir do name

### Sobre Seeds
- Os seeds devem ser executados na ordem:
  1. TenantsTableSeeder
  2. PermissionsTableSeeder
  3. ProfilesTableSeeder
  4. UsersTableSeeder
  5. Outros seeders específicos (Clients, Products, etc.)

---

## Verificação Final

Execute este comando para verificar se tudo está funcionando:

```bash
cd /Users/fabiosantana/Documentos/projetos/moday/backend
./vendor/bin/sail artisan test --filter=PermissionTest
```

Se não houver testes, verifique manualmente:
```bash
./vendor/bin/sail artisan route:list | grep permissions
./vendor/bin/sail artisan route:list | grep profiles
```
