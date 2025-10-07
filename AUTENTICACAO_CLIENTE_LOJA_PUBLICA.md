# Sistema de Autenticação de Clientes - Loja Pública

## Implementação Completa

### Backend

#### 1. Controller de Autenticação
**Arquivo:** `backend/app/Http/Controllers/Api/ClientAuthController.php`

Endpoints criados:
- `POST /api/store/{slug}/auth/register` - Registro de novo cliente
- `POST /api/store/{slug}/auth/login` - Login de cliente
- `GET /api/store/{slug}/auth/me` - Dados do cliente autenticado (requer token)
- `POST /api/store/{slug}/auth/logout` - Logout de cliente (requer token)

**Funcionalidades:**
- ✅ Registro com validação de email único por tenant
- ✅ Login com verificação de senha hash
- ✅ Geração de JWT token
- ✅ Validação de cliente ativo
- ✅ Proteção com throttling (rate limiting)

#### 2. Rotas Adicionadas
**Arquivo:** `backend/routes/api.php`

```php
Route::prefix('store/{slug}')->group(function () {
    // Client Authentication
    Route::post('/auth/register', [ClientAuthController::class, 'register'])
        ->middleware('throttle:5,1'); // 5 registrations per minute
    
    Route::post('/auth/login', [ClientAuthController::class, 'login'])
        ->middleware('throttle:10,1'); // 10 login attempts per minute
    
    Route::middleware('auth:api')->group(function () {
        Route::get('/auth/me', [ClientAuthController::class, 'me']);
        Route::post('/auth/logout', [ClientAuthController::class, 'logout']);
    });
});
```

#### 3. Campo Senha Opcional ao Criar Pedido
**Arquivo:** `backend/app/Http/Controllers/Api/PublicStoreController.php`

Modificações no método `createOrder`:
```php
$validated = $request->validate([
    'client.password' => 'nullable|string|min:6', // ← NOVO campo opcional
    // ... outros campos
]);

// Adiciona password se fornecido
if (!empty($validated['client']['password'])) {
    $clientData['password'] = \Hash::make($validated['client']['password']);
}
```

**Comportamento:**
- Se o cliente fornecer senha ao fazer pedido → cria conta com senha
- Se não fornecer senha → cria conta sem senha (guest checkout)
- Permite que cliente crie conta durante o checkout

---

### Frontend

#### 1. Contexto de Autenticação
**Arquivo:** `frontend/src/contexts/client-auth-context.tsx`

**Provider criado:**
```tsx
<ClientAuthProvider>
  {children}
</ClientAuthProvider>
```

**Hook disponível:**
```tsx
const { 
  client,           // Dados do cliente logado
  token,            // JWT token
  isAuthenticated,  // Status de autenticação
  isLoading,        // Loading inicial
  login,            // Função de login
  register,         // Função de registro
  logout,           // Função de logout
  setClient         // Atualizar dados do cliente
} = useClientAuth()
```

**Armazenamento:**
- localStorage: `client-auth-user` (dados do cliente)
- localStorage: `client-auth-token` (JWT token)

---

## API Endpoints

### 1. Registro de Cliente

**Endpoint:** `POST /api/store/{slug}/auth/register`

**Request:**
```json
{
  "name": "João Silva",
  "email": "joao@exemplo.com",
  "password": "senha123",
  "password_confirmation": "senha123",
  "phone": "11987654321",
  "cpf": "12345678901"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Cliente registrado com sucesso",
  "data": {
    "client": {
      "uuid": "...",
      "name": "João Silva",
      "email": "joao@exemplo.com",
      "phone": "11987654321",
      "cpf": "12345678901"
    },
    "token": "eyJ0eXAiOiJKV1QiLC...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Erros Possíveis:**
- 404: Loja não encontrada
- 422: Email já cadastrado ou dados inválidos
- 500: Erro interno

---

### 2. Login de Cliente

**Endpoint:** `POST /api/store/{slug}/auth/login`

**Request:**
```json
{
  "email": "joao@exemplo.com",
  "password": "senha123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "client": {
      "uuid": "...",
      "name": "João Silva",
      "email": "joao@exemplo.com",
      "phone": "11987654321",
      "cpf": "12345678901",
      "address": "Rua das Flores",
      "city": "São Paulo",
      "state": "SP",
      "zip_code": "01234-567",
      "neighborhood": "Centro",
      "number": "123",
      "complement": "Apto 45"
    },
    "token": "eyJ0eXAiOiJKV1QiLC...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Erros Possíveis:**
- 401: Email ou senha incorretos
- 403: Conta desativada
- 404: Loja não encontrada

---

### 3. Dados do Cliente Autenticado

**Endpoint:** `GET /api/store/{slug}/auth/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "uuid": "...",
    "name": "João Silva",
    "email": "joao@exemplo.com",
    "phone": "11987654321",
    "cpf": "12345678901",
    "address": "Rua das Flores",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01234-567",
    "neighborhood": "Centro",
    "number": "123",
    "complement": "Apto 45"
  }
}
```

---

### 4. Logout

**Endpoint:** `POST /api/store/{slug}/auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logout realizado com sucesso"
}
```

---

### 5. Criar Pedido (com senha opcional)

**Endpoint:** `POST /api/store/{slug}/orders`

**Request (COM senha - cria conta):**
```json
{
  "client": {
    "name": "João Silva",
    "email": "joao@exemplo.com",
    "phone": "11987654321",
    "cpf": "12345678901",
    "password": "senha123"  // ← OPCIONAL: Cria conta com senha
  },
  "delivery": {
    "is_delivery": true,
    "address": "Rua das Flores",
    "number": "123",
    // ... outros campos
  },
  "products": [
    {
      "uuid": "product-uuid",
      "quantity": 2
    }
  ],
  "payment_method": "pix",
  "shipping_method": "delivery"
}
```

**Request (SEM senha - guest checkout):**
```json
{
  "client": {
    "name": "João Silva",
    "email": "joao@exemplo.com",
    "phone": "11987654321",
    "cpf": null
    // SEM campo password → Cliente sem senha
  },
  // ... resto igual
}
```

---

## Fluxos de Uso

### Fluxo 1: Cliente Novo com Registro

```
1. Cliente acessa loja → Navega produtos
2. Adiciona produtos ao carrinho
3. Vai para checkout
4. Escolhe "Criar conta" (fornece senha)
5. Completa dados de entrega
6. Finaliza pedido
   → Cliente criado COM senha
   → Login automático
   → Token salvo no localStorage
7. Cliente pode acessar "Minha Conta" posteriormente
```

### Fluxo 2: Cliente Novo sem Registro (Guest)

```
1. Cliente acessa loja → Navega produtos
2. Adiciona produtos ao carrinho
3. Vai para checkout
4. Escolhe "Continuar sem cadastro" (não fornece senha)
5. Completa dados de entrega
6. Finaliza pedido
   → Cliente criado SEM senha
   → Não pode fazer login posteriormente
```

### Fluxo 3: Cliente Existente (Login)

```
1. Cliente acessa loja
2. Clica em "Login"
3. Informa email e senha
4. Sistema valida → retorna token
5. Dados salvos em localStorage
6. Cliente logado pode:
   - Ver dados pré-preenchidos no checkout
   - Acessar histórico de pedidos (se implementado)
   - Atualizar dados cadastrais
```

---

## Exemplo de Uso Frontend

### 1. Wrap App com Provider

```tsx
// app/layout.tsx ou app/store/[slug]/layout.tsx
import { ClientAuthProvider } from '@/contexts/client-auth-context'

export default function StoreLayout({ children }) {
  return (
    <ClientAuthProvider>
      {children}
    </ClientAuthProvider>
  )
}
```

### 2. Usar em Componente

```tsx
'use client'

import { useClientAuth } from '@/contexts/client-auth-context'
import { useState } from 'react'

export function LoginForm({ slug }: { slug: string }) {
  const { login, isAuthenticated, client } = useClientAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      await login(email, password, slug)
      // Login bem-sucedido!
    } catch (err: any) {
      setError(err.message)
    }
  }

  if (isAuthenticated) {
    return <div>Bem-vindo, {client?.name}!</div>
  }

  return (
    <form onSubmit={handleLogin}>
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="Email"
      />
      <input
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        placeholder="Senha"
      />
      {error && <p className="text-red-500">{error}</p>}
      <button type="submit">Entrar</button>
    </form>
  )
}
```

### 3. Pré-preencher Checkout para Cliente Logado

```tsx
const { client, isAuthenticated } = useClientAuth()

useEffect(() => {
  if (isAuthenticated && client) {
    setClientData({
      name: client.name,
      email: client.email,
      phone: client.phone,
      cpf: client.cpf || '',
    })
    
    if (client.address) {
      setDeliveryData({
        is_delivery: true,
        address: client.address,
        number: client.number || '',
        neighborhood: client.neighborhood || '',
        city: client.city || '',
        state: client.state || '',
        zip_code: client.zip_code || '',
        complement: client.complement || '',
        notes: '',
      })
    }
  }
}, [isAuthenticated, client])
```

---

## Segurança

### Rate Limiting
- ✅ Registro: 5 tentativas por minuto
- ✅ Login: 10 tentativas por minuto
- ✅ Criar pedido: 10 por minuto

### Senhas
- ✅ Mínimo 6 caracteres
- ✅ Hash com bcrypt
- ✅ Campo opcional (permite guest checkout)

### Tokens JWT
- ✅ Expiração configurável
- ✅ Armazenamento seguro no localStorage
- ✅ Validação em rotas protegidas

---

## Próximos Passos Sugeridos

### Frontend
- [ ] Criar página de login separada
- [ ] Criar página de registro
- [ ] Adicionar botão "Login" no header da loja
- [ ] Mostrar dados do cliente logado
- [ ] Permitir logout
- [ ] Pré-preencher formulário para clientes logados
- [ ] Histórico de pedidos do cliente
- [ ] Edição de perfil

### Backend
- [ ] Recuperação de senha (forgot password)
- [ ] Verificação de email
- [ ] Atualização de dados do cliente
- [ ] Histórico de pedidos por cliente

### UX
- [ ] Checkbox "Criar conta" no checkout
- [ ] Toggle "Já tem conta? Faça login"
- [ ] Mensagens de feedback amigáveis
- [ ] Loading states
- [ ] Validação em tempo real
