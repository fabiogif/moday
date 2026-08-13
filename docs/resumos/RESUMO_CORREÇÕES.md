# Resumo das Correções - Sistema de Profiles e Permissions

## 📋 Problemas Corrigidos

### 1. ✅ Erro 404 ao vincular permissões ao perfil

**Erro anterior:**
```
PUT http://localhost/api/profile/2/permissions/sync 404 (Not Found)
ApiClient: Erro HTTP 404 : Perfil não encontrado
```

**Solução:**
- Corrigido endpoint de `/api/profile` para `/api/profiles` no arquivo `frontend/src/lib/api-client.ts`
- O backend espera o endpoint plural (`/api/profiles`), mas o frontend estava usando singular (`/api/profile`)

**Arquivo modificado:**
- `frontend/src/lib/api-client.ts` (linhas 288-295)

---

### 2. ✅ Modal "Vincular Permissões" mostrando array vazio

**Erro anterior:**
```
Console: Permissoes carregadas: []
Modal mostrando: "Nenhuma permissão disponível"
```

**Solução:**
- Melhorado o tratamento de dados na função `filterPermissions()` do componente `assign-permissions-dialog.tsx`
- Agora trata corretamente os diferentes formatos de resposta da API:
  - Caso 1: `allPermissions` é um array direto
  - Caso 2: `allPermissions` é um objeto com `{ permissions: [...], pagination: {...} }`
  - Caso 3: `allPermissions` é um objeto com `{ data: { permissions: [...] } }`

**Arquivo modificado:**
- `frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx` (função `filterPermissions`)

---

## 🔍 Estrutura Confirmada

### Arquitetura de Permissões

```
┌─────────┐      ┌──────────┐      ┌──────────────┐
│  User   │─────▶│ Profile  │─────▶│ Permissions  │
└─────────┘      └──────────┘      └──────────────┘
```

**Relacionamentos:**
- Um **User** pode ter um ou mais **Profiles**
- Um **Profile** pode ter várias **Permissions**
- **Permissions** são identificadas por `slug` (ex: `users.index`, `clients.view`)

---

## 🌐 Endpoints da API

### Perfis (Profiles)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/profiles` | Listar perfis |
| POST | `/api/profiles` | Criar perfil |
| GET | `/api/profiles/{id}` | Visualizar perfil |
| PUT | `/api/profiles/{id}` | Atualizar perfil |
| DELETE | `/api/profiles/{id}` | Excluir perfil |

### Permissões do Perfil

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/profiles/{id}/permissions` | Listar permissões do perfil |
| GET | `/api/profiles/{id}/permissions/available` | Listar permissões disponíveis |
| POST | `/api/profiles/{id}/permissions` | Adicionar permissão individual |
| DELETE | `/api/profiles/{id}/permissions/{permissionId}` | Remover permissão |
| **PUT** | **`/api/profiles/{id}/permissions/sync`** | **Sincronizar permissões** ✅ |

### Payload do Sync

```json
{
  "permission_ids": [1, 2, 3, 4, 5]
}
```

**Validações:**
- ✅ `permission_ids` é obrigatório
- ✅ `permission_ids` deve ser um array
- ✅ Todos os IDs devem existir na tabela `permissions`
- ✅ Todas as permissões devem pertencer ao mesmo tenant do usuário

---

## 🔒 Segurança

O backend implementa:

1. **Tenant Isolation**
   - Todos os perfis e permissões são verificados para garantir que pertencem ao mesmo tenant do usuário autenticado
   - Impede acesso cruzado entre tenants

2. **Validação de Dados**
   - Request validation usando `PermissionProfileSyncRequest`
   - Verifica existência de permissões antes de sincronizar

3. **Transações**
   - Usa DB transactions para garantir consistência dos dados
   - Rollback automático em caso de erro

---

## 📝 Notas Importantes

### Roles vs Profiles

- ❌ **Roles** foram descontinuadas
- ✅ **Profiles** é o padrão recomendado
- ⚠️ O campo `slug` não é mais usado em Profiles (era específico de Roles)

### Permission Slugs

Permissões usam `slug` para identificação no código:

```php
// Exemplos de slugs de permissões
users.index      // Visualizar lista de usuários
users.create     // Criar usuário
users.update     // Editar usuário
users.delete     // Excluir usuário
clients.view     // Visualizar clientes
products.edit    // Editar produtos
```

---

## 🧪 Como Testar

1. **Acessar a página de Perfis**
   - URL: `/profiles`

2. **Clicar em "Vincular Permissões" em um perfil**
   - Deve abrir o modal
   - Deve mostrar todas as permissões disponíveis agrupadas por módulo

3. **Selecionar permissões**
   - Marcar/desmarcar checkboxes
   - Usar botão "Selecionar Todas" ou "Limpar Seleção"
   - Buscar permissões pelo campo de pesquisa

4. **Salvar permissões**
   - Clicar em "Salvar Permissões"
   - Deve mostrar toast de sucesso
   - Modal deve fechar
   - Lista deve ser atualizada

---

## 🐛 Troubleshooting

### Problema: "Nenhuma permissão disponível"

**Verificar:**
1. Permissões foram criadas no banco? 
   ```bash
   php artisan db:seed --class=PermissionSeeder
   ```

2. Usuário está autenticado?
   - Verificar token JWT no localStorage/cookie

3. Console do navegador mostra erros?
   - Abrir DevTools (F12) → Console

### Problema: "Perfil não encontrado"

**Verificar:**
1. ID do perfil existe?
2. Perfil pertence ao tenant do usuário?
3. Endpoint está correto (`/api/profiles` e não `/api/profile`)?

### Problema: "Os IDs das permissões são obrigatórios"

**Verificar:**
1. Payload está sendo enviado corretamente?
   ```json
   {
     "permission_ids": [1, 2, 3]
   }
   ```

2. Array não está vazio?
3. IDs são números válidos?

---

## ✅ Checklist de Validação

- [x] Endpoint de profiles corrigido para `/api/profiles`
- [x] Função `filterPermissions()` trata múltiplos formatos de resposta
- [x] Modal de permissões carrega e exibe permissões corretamente
- [x] Sincronização de permissões funciona (PUT)
- [x] Validações do backend funcionam corretamente
- [x] Tenant isolation está funcionando
- [x] Transações garantem consistência

---

## 📚 Arquivos Modificados

```
frontend/src/lib/api-client.ts
frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx
```

## 📄 Arquivos Criados

```
CORREÇÕES_PROFILES.md (este arquivo)
frontend/src/app/(dashboard)/profiles/components/assign-permissions-dialog.tsx (novo componente)
```

---

**Data da correção:** 04/10/2025  
**Versão:** 1.0
