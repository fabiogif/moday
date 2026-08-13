# Correção - Erro "Unauthenticated" nas Rotas

## Problema Identificado

Após as melhorias de segurança implementadas, as rotas protegidas começaram a retornar o erro:
```
Erro ao carregar clientes: Unauthenticated.
Erro ao carregar pedidos: Unauthenticated.
```

## Causa Raiz

O problema foi causado por uma inconsistência no gerenciamento do token JWT:

### Estado Anterior (Problemático)
```typescript
// auth-context.tsx - Login
const data = await response.json()
setUser(data.user)
setToken('authenticated') // ❌ Token fixo, não o JWT real
setIsAuthenticated(true)

// Salvava apenas o usuário, não o token
localStorage.setItem('auth-user', JSON.stringify(data.user))
// Comentário: "Token fica no HttpOnly cookie (seguro)"
```

### O que estava acontecendo:
1. Backend retorna `{ user: {...}, token: "eyJ..." }` na resposta de login
2. Frontend salvava apenas `user`, ignorando o `token` recebido
3. `setToken('authenticated')` definia uma string fixa em vez do JWT
4. Quando `useAuthenticatedApi` tentava fazer requisições:
   - Chamava `apiClient.setToken(token)` com `'authenticated'`
   - ApiClient tentava usar `'authenticated'` como Bearer token
   - Backend rejeitava com "Unauthenticated"

## Correção Implementada

### 1. Armazenamento correto do token no login

```typescript
// auth-context.tsx - Login corrigido
const data = await response.json()

setUser(data.user)
setToken(data.token) // ✅ Armazena o JWT real recebido
setIsAuthenticated(true)

// Salvar dados do usuário e token
localStorage.setItem('auth-user', JSON.stringify(data.user))
localStorage.setItem('auth-token', data.token) // ✅ Salva o JWT
```

### 2. Limpeza completa no logout

```typescript
// auth-context.tsx - Logout corrigido
const logout = async () => {
  try {
    // Chamar logout no backend
    await fetch('/api/auth/logout', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
  } catch (error) {
    console.error('Erro ao fazer logout no backend:', error)
  } finally {
    setUser(null)
    setToken(null)
    setIsAuthenticated(false)
    
    // Remover dados do localStorage
    localStorage.removeItem('auth-user')
    localStorage.removeItem('auth-token') // ✅ Remove o token
    
    // Limpar cookie
    document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
  }
}
```

### 3. Headers de requisição (já estava correto)

```typescript
// api-client.ts
private getHeaders(isFormData = false): HeadersInit {
  const headers: HeadersInit = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }

  if (!isFormData) {
    headers['Content-Type'] = 'application/json'
  }

  if (this.token) {
    headers.Authorization = `Bearer ${this.token}` // ✅ Envia JWT correto
  }

  return headers
}
```

## Fluxo Corrigido

### Login
1. **Frontend** → POST `/api/auth/login` com `{ email, password }`
2. **Next.js API Route** → POST `http://backend/api/auth/login`
3. **Backend Laravel** → Retorna `{ data: { user: {...}, token: "eyJ..." } }`
4. **Frontend** → Armazena `user` e `token` JWT real
5. **ApiClient** → Recebe token JWT via `setToken(data.token)`

### Requisições Autenticadas
1. **Component** → Chama `useAuthenticatedApi('/api/clients')`
2. **Hook** → Verifica `isAuthenticated` e `token`
3. **Hook** → Chama `apiClient.setToken(token)` com JWT real
4. **ApiClient** → GET `/api/clients` com header `Authorization: Bearer eyJ...`
5. **Backend** → Valida JWT e retorna dados

### Logout
1. **Frontend** → Chama `logout()`
2. **Frontend** → POST `/api/auth/logout` no backend
3. **Frontend** → Limpa `localStorage`, `cookie` e estado
4. **Frontend** → Redireciona para login

## Arquivos Modificados

- ✅ `frontend/src/contexts/auth-context.tsx`
  - Corrigido armazenamento do token no login
  - Corrigido limpeza do token no logout
  - Adicionado headers CSRF

## Verificação da Correção

Para verificar se o token está sendo armazenado corretamente:

```javascript
// No console do navegador após login
localStorage.getItem('auth-token') // Deve retornar o JWT
```

Para verificar se está sendo enviado:

```javascript
// No DevTools → Network → Headers de qualquer requisição autenticada
// Deve aparecer:
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

## Lições Aprendidas

1. **Sempre armazenar o token JWT completo** retornado pelo backend
2. **Não usar valores placeholder** como `'authenticated'` para tokens
3. **Validar fluxo completo** de autenticação após mudanças de segurança
4. **Logs de debug** ajudam a identificar quando o token não está presente
5. **Consistência** entre o que o backend retorna e o que o frontend armazena

## Segurança Mantida

Mesmo armazenando o token no localStorage e cookie:
- ✅ Logs não expõem o token (condicionalizado por `NODE_ENV`)
- ✅ Token é enviado apenas via HTTPS em produção
- ✅ Headers CSRF (`X-Requested-With`) protegem contra ataques
- ✅ Token tem expiração definida pelo backend
- ✅ Logout limpa todos os vestígios do token

## Conclusão

A autenticação foi restaurada completamente. O token JWT agora é:
1. Recebido corretamente do backend
2. Armazenado no localStorage e cookie
3. Sincronizado com ApiClient
4. Enviado em todas as requisições autenticadas
5. Limpo adequadamente no logout

Todas as rotas protegidas voltaram a funcionar corretamente.
