# Instruções para Resolver "Unauthenticated"

## Problema

O erro "Erro ao carregar pedidos: 11" está ocorrendo porque há um token antigo e inválido armazenado no navegador.

## Causa

Antes das correções, o código estava salvando `'authenticated'` (string) em vez do token JWT real. Se você já estava logado antes das correções, esse token inválido ainda está no seu `localStorage`.

## Solução Rápida

### Opção 1: Usar o Botão de Debug (Recomendado)

1. **Abra a aplicação** em `http://localhost:3000` ou `http://localhost:3001`
2. **Procure no canto superior direito** por um botão vermelho "Forçar Logout"
3. **Clique no botão** para limpar toda autenticação
4. **Faça login novamente** com suas credenciais

### Opção 2: Console do Navegador

1. Abra o **DevTools** (F12)
2. Vá na aba **Console**
3. Execute os seguintes comandos:

```javascript
// Verificar o token atual
localStorage.getItem('auth-token')

// Se não começar com "eyJ", está inválido!
// Limpar tudo:
localStorage.removeItem('auth-user')
localStorage.removeItem('auth-token')
document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'

// Recarregar página
location.reload()
```

4. **Faça login novamente**

### Opção 3: Application Tab

1. Abra o **DevTools** (F12)
2. Vá na aba **Application** (ou Aplicação)
3. No menu lateral:
   - **Local Storage** → Clique no seu domínio → Delete `auth-token` e `auth-user`
   - **Cookies** → Clique no seu domínio → Delete `auth-token`
4. **Recarregue a página** (F5)
5. **Faça login novamente**

## Como Verificar se o Token é Válido

No console do navegador:

```javascript
const token = localStorage.getItem('auth-token')
console.log('Token presente?', !!token)
console.log('É JWT válido?', token?.startsWith('eyJ'))
console.log('Comprimento:', token?.length)
```

### Token Válido (JWT):
```
Token presente? true
É JWT válido? true
Comprimento: 200+ caracteres
```

### Token Inválido:
```
Token presente? true
É JWT válido? false  ← PROBLEMA!
Comprimento: 13 (apenas "authenticated")
```

## Logs de Debug

Após as correções, o sistema mostra logs úteis no console (apenas em desenvolvimento):

```
AuthContext: Inicializando autenticação
AuthContext: Token presente? true
AuthContext: Token é JWT? false  ← Se false, é o problema!
AuthContext: Token inválido encontrado (não é JWT). Limpando...
```

Ou quando válido:
```
AuthContext: Inicializando autenticação
AuthContext: Token presente? true
AuthContext: Token é JWT? true  ← Correto!
AuthContext: Autenticação restaurada com sucesso
```

## Após Fazer Login Novamente

Verifique no console:
```
AuthContext: Login bem-sucedido
AuthContext: Token recebido? true
AuthContext: Token é JWT? true
```

E as requisições devem funcionar:
```
ApiClient: GET: http://localhost/api/order
AuthenticatedApi: Fazendo requisição para: /api/order
AuthenticatedApi: Resposta recebida: {success: true, ...}
```

## Proteções Implementadas

O sistema agora:

1. ✅ **Valida o token ao inicializar** - Limpa automaticamente se não for JWT
2. ✅ **Valida o token ao fazer login** - Rejeita se não for JWT válido
3. ✅ **Logs condicionais** - Mostra informações úteis apenas em dev
4. ✅ **Botão de emergência** - ForceLogoutButton para limpar tudo

## Prevenção Futura

Isso não deve mais acontecer porque:
- O sistema valida o formato do token (deve começar com "eyJ")
- Tokens inválidos são automaticamente limpos
- Logs ajudam a identificar problemas rapidamente

## Precisa de Ajuda?

Se ainda estiver com problemas:

1. Abra o console do navegador
2. Copie TODOS os logs que aparecem
3. Verifique se há algum erro em vermelho
4. Compartilhe os logs para diagnóstico
