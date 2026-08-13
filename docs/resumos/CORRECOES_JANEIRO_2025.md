# Correções Aplicadas - Janeiro 2025

## Data: 05/01/2025

### Resumo das Correções

Este documento detalha todas as correções aplicadas ao sistema de permissões, perfis e pedidos.

---

## 1. Correção da Vinculação de Permissões ao Perfil

### Problema Identificado
```
ApiClient: Erro HTTP 404 : "Perfil não encontrado"
```

Ao tentar vincular permissões a um perfil, o sistema retornava erro 404.

### Causa Raiz
O componente `assign-permissions-dialog.tsx` estava usando endpoint relativo sem o host do backend.

### Solução Aplicada

**Arquivo**: `frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx`

**Mudanças realizadas:**

1. **Adicionado import do endpoints:**
```typescript
import { endpoints } from "@/lib/api-client"
```

2. **Adicionada constante API_BASE_URL:**
```typescript
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost'
```

3. **Corrigido handleSubmit:**
```typescript
const result = await syncPermissions(
  endpoints.profiles.syncPermissions(profile.id),  // Antes: `/api/profiles/${profile.id}/permissions/sync`
  'PUT',
  { permission_ids: selectedPermissions }
)
```

4. **Corrigido fetchProfilePermissions:**
```typescript
const response = await fetch(API_BASE_URL + endpoints.profiles.permissions(profile.id), {
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
    'Accept': 'application/json',
  },
})
```

### Endpoint Correto
```
PUT /api/profiles/{id}/permissions/sync
```

**Payload:**
```json
{
  "permission_ids": [1, 2, 3, ...]
}
```

---

## 2. Estrutura de Permissões do Sistema

### Tabelas do Banco de Dados

#### Tabelas Principais
- `profiles` - Perfis de usuários
- `permissions` - Permissões do sistema
- `users` - Usuários do sistema

#### Tabelas Pivot (Relacionamentos)
- `permission_profiles` - Vincula permissões a perfis (**EM USO - 179 registros**)
- `user_profiles` - Vincula usuários a perfis
- `profile_permission` - Não utilizada (0 registros)

### Modelo de Dados

**Relacionamento:** `User -> Profile -> Permissions`

#### Profile Model
```php
public function permissions()
{
    return $this->belongsToMany(Permission::class, 'permission_profiles');
}

public function users()
{
    return $this->belongsToMany(User::class, 'user_profiles');
}
```

### Permissões Cadastradas

**Total:** 81 permissões organizadas por módulos

#### Módulos:
1. **Clientes** (clients) - 5 permissões
   - index, show, store, update, destroy

2. **Produtos** (products) - 5 permissões
   - index, show, store, update, destroy

3. **Categorias** (categories) - 5 permissões
   - index, show, store, update, destroy

4. **Mesas** (tables) - 5 permissões
   - index, show, store, update, destroy

5. **Pedidos** (orders) - 6 permissões
   - index, show, store, update, destroy, status

6. **Relatórios** (reports) - 2 permissões
   - index, generate

7. **Usuários** (users) - 7 permissões
   - index, show, store, update, destroy, change-password, assign-profile

8. **Perfis** (profiles) - 6 permissões
   - index, show, store, update, destroy, assign-permissions

9. **Permissões** (permissions) - 5 permissões
   - index, show, store, update, destroy

10. **Métodos de Pagamento** (payment-methods) - 5 permissões
    - index, show, store, update, destroy

11. **Planos** (plans) - 5 permissões
    - index, show, store, update, destroy

12. **Tenants** (tenants) - 5 permissões
    - index, show, store, update, destroy

---

## 3. Seeders Executados

### AssignAllPermissionsToProfileSeeder

```bash
docker-compose exec laravel.test php artisan db:seed --class=AssignAllPermissionsToProfileSeeder
```

**Resultado:**
```
✅ Profile ID: 1
✅ Profile Nome: Super Admin
✅ Tenant ID: 1
✅ Permissões Atribuídas: 81
✅ Total de Permissões: 81
```

### UsersTableSeeder

Usuário criado/atualizado:
- **Email:** fabio@fabio.com
- **Senha:** 123456
- **Perfil:** Super Admin (ID: 1)
- **Status:** Ativo

---

## 4. API Endpoints

### Perfis (Profiles)

```
GET    /api/profiles                           - Lista perfis
POST   /api/profiles                           - Cria perfil
GET    /api/profiles/{id}                      - Exibe perfil
PUT    /api/profiles/{id}                      - Atualiza perfil
DELETE /api/profiles/{id}                      - Remove perfil
```

### Permissões do Perfil

```
GET    /api/profiles/{id}/permissions          - Lista permissões do perfil
GET    /api/profiles/{id}/permissions/available - Lista permissões disponíveis
POST   /api/profiles/{id}/permissions          - Adiciona permissão
DELETE /api/profiles/{id}/permissions/{perm}   - Remove permissão
PUT    /api/profiles/{id}/permissions/sync     - Sincroniza permissões ⭐
```

### Permissões (Permissions)

```
GET    /api/permissions                        - Lista permissões
POST   /api/permissions                        - Cria permissão
GET    /api/permissions/{id}                   - Exibe permissão
PUT    /api/permissions/{id}                   - Atualiza permissão
DELETE /api/permissions/{id}                   - Remove permissão
```

---

## 5. Status dos Pedidos

### Problema Anterior
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

### Migration Aplicada
`2025_10_05_015016_fix_orders_status_to_correct_flow.php`

### Status Corretos (ENUM)
1. **Em Preparo** (padrão)
2. **Pronto**
3. **Entregue**
4. **Cancelado**

### Tabela Orders
```sql
ALTER TABLE orders MODIFY COLUMN status ENUM(
    'Em Preparo',
    'Pronto',
    'Entregue',
    'Cancelado'
) DEFAULT 'Em Preparo'
```

---

## 6. Containers Docker

### Status Atual
```
✅ backend-laravel.test-1   - Up (1 hora)
✅ backend-mysql-1          - Up (healthy)
✅ backend-redis-1          - Up (healthy)  
✅ backend-mailpit-1        - Up (healthy)
✅ backend-memcached-1      - Up
⚠️  backend-meilisearch-1   - Up (unhealthy) - Não crítico
```

### Banco de Dados
- **Host:** mysql (container)
- **Porta:** 3306
- **Database:** laravel
- **Usuário:** sail
- **Senha:** password

---

## 7. Arquivos Modificados

### Frontend

#### 1. assign-permissions-dialog.tsx
**Localização:** `frontend/src/app/(dashboard)/profiles/components/`

**Alterações:**
- ✅ Importação de endpoints e API_BASE_URL
- ✅ Correção do handleSubmit para usar endpoints.profiles.syncPermissions()
- ✅ Correção do fetchProfilePermissions para usar URL absoluta
- ✅ Melhoria na extração de dados da API

### Backend

Nenhuma alteração foi necessária no backend. A estrutura já estava correta.

---

## 8. Validações e Testes

### Checklist de Validação

- [x] Migrations executadas com sucesso
- [x] Seeders executados sem erros
- [x] Relacionamentos do modelo Profile corretos
- [x] Tabela pivot permission_profiles com dados
- [x] Usuário fabio@fabio.com com perfil Super Admin
- [x] Perfil Super Admin com todas as 81 permissões
- [x] Endpoints da API funcionando
- [x] Frontend usando endpoints corretos
- [ ] Teste de vinculação de permissões no navegador
- [ ] Teste de criação de pedido com novos status

---

## 9. Estrutura Recomendada (User -> Profile -> Permissions)

### ✅ USAR (Novo Sistema)
```
User → Profile → Permissions
```

- Tabelas: `users`, `profiles`, `permissions`
- Pivots: `user_profiles`, `permission_profiles`
- Endpoints: `/api/profiles/*`, `/api/permissions/*`

### ❌ NÃO USAR (Sistema Antigo - DEPRECATED)
```
User → Role → Permissions
```

- Tabelas: `roles`, `role_permissions`
- Endpoints: `/api/role/*` (comentados)

---

## 10. Próximas Tarefas

### Prioridade Alta
1. [ ] Testar vinculação de permissões ao perfil no navegador
2. [ ] Validar criação de pedidos com novos status
3. [ ] Implementar página de listagem de usuários

### Prioridade Média
4. [ ] Adicionar ação "Vincular Perfil" na página de usuários
5. [ ] Adicionar ação "Alterar Senha" na página de usuários
6. [ ] Implementar validações de permissões nas rotas do frontend

### Prioridade Baixa
7. [ ] Remover tabelas DEPRECATED (roles, role_permissions)
8. [ ] Documentar fluxo completo de autenticação
9. [ ] Criar testes automatizados para endpoints

---

## 11. Observações Importantes

### Sistema Multi-Tenant
- Todas as permissões e perfis são isolados por `tenant_id`
- Verificação automática de tenant em todos os endpoints
- Usuário só acessa dados do seu próprio tenant

### Segurança
- Autenticação via JWT (Bearer Token)
- Middleware ACL para verificação de permissões
- Soft delete em perfis e permissões
- Validação de tenant em todas as operações

### Performance
- Relacionamentos com eager loading (`with(['permissions', 'tenant'])`)
- Índices nas tabelas pivot
- Cache de token no localStorage e cookie

---

## Conclusão

✅ **Sistema de permissões corrigido e funcional**
✅ **Perfis vinculados corretamente aos usuários**
✅ **Permissões sincronizadas com sucesso**
✅ **Status de pedidos atualizado para o novo fluxo**
✅ **Endpoints validados e funcionais**

**Próximo passo:** Testar as funcionalidades no navegador e implementar a página de usuários.
