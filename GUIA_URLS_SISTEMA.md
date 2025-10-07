# 📍 URLs do Sistema - Admin vs Loja Pública

## 🔐 PAINEL ADMINISTRATIVO (Admin)

### URLs Principais
```
Base: http://localhost:3000

Login:        http://localhost:3000/sign-in
Dashboard:    http://localhost:3000/dashboard
Produtos:     http://localhost:3000/products
Pedidos:      http://localhost:3000/orders
Clientes:     http://localhost:3000/clients
Categorias:   http://localhost:3000/categories
Mesas:        http://localhost:3000/tables
Usuários:     http://localhost:3000/users
Perfis:       http://localhost:3000/profiles
Configurações: http://localhost:3000/settings
```

### Características
- **Usuário:** Admin/Usuário (tabela `users`)
- **Autenticação:** Obrigatória
- **JWT Guard:** `api`
- **Pedidos - Origin:** `admin`
- **Acesso:** Gerenciar todo o sistema

---

## 🛍️ LOJA PÚBLICA (Public Store)

### URL Principal
```
http://localhost:3000/store/{slug}

Exemplo:
http://localhost:3000/store/empresa-dev
```

### Funcionalidades Disponíveis
- ✅ Catálogo de produtos
- ✅ Carrinho de compras
- ✅ Checkout (com ou sem cadastro)
- ✅ Login de cliente (opcional)
- ✅ Registro de cliente (opcional)

### URLs de Autenticação (quando implementadas)
```
Login:     http://localhost:3000/store/empresa-dev/login
Registro:  http://localhost:3000/store/empresa-dev/register
Perfil:    http://localhost:3000/store/empresa-dev/account
```

### Características
- **Usuário:** Cliente (tabela `clients`)
- **Autenticação:** Opcional
- **JWT Guard:** `client`
- **Pedidos - Origin:** `public_store`
- **Acesso:** Fazer pedidos e ver produtos

---

## 🔄 COMPARAÇÃO LADO A LADO

| Característica | Admin | Loja Pública |
|----------------|-------|--------------|
| **URL Base** | `http://localhost:3000` | `http://localhost:3000/store/{slug}` |
| **Login** | `/sign-in` | `/store/{slug}/login` |
| **Tabela de Usuários** | `users` | `clients` |
| **Autenticação** | Obrigatória | Opcional |
| **JWT Guard** | `api` | `client` |
| **Origin Pedidos** | `admin` | `public_store` |
| **Pode Gerenciar** | Todo sistema | Apenas próprios pedidos |
| **Acessa Dashboard** | ✅ Sim | ❌ Não |

---

## 📍 ENDPOINTS API

### API Admin
```bash
Base URL: http://localhost:8000/api

# Login Admin/Usuário
POST /api/auth/login
Body: { "email": "user@email.com", "password": "senha" }
Response: { "token": "...", "user": {...} }

# Criar Pedido (origin: admin)
POST /api/order
Headers: Authorization: Bearer {token}
```

### API Loja Pública
```bash
Base URL: http://localhost:8000/api/store/{slug}

# Informações da Loja
GET /api/store/empresa-dev/info

# Produtos Disponíveis
GET /api/store/empresa-dev/products

# Registro de Cliente
POST /api/store/empresa-dev/auth/register
Body: {
  "name": "João Silva",
  "email": "joao@email.com",
  "password": "senha123",
  "password_confirmation": "senha123",
  "phone": "11999999999"
}

# Login de Cliente
POST /api/store/empresa-dev/auth/login
Body: { "email": "joao@email.com", "password": "senha123" }

# Dados do Cliente Logado
GET /api/store/empresa-dev/auth/me
Headers: Authorization: Bearer {token}

# Criar Pedido (origin: public_store)
POST /api/store/empresa-dev/orders
Body: {
  "client": { "name": "...", "email": "...", "phone": "..." },
  "delivery": { "is_delivery": true, ... },
  "products": [{ "uuid": "...", "quantity": 2 }],
  "payment_method": "pix",
  "shipping_method": "delivery"
}
```

---

## 🎯 IDENTIFICAR ORIGEM DO PEDIDO

### No Banco de Dados
```sql
SELECT 
    identify,
    origin,
    status,
    total,
    created_at,
    CASE 
        WHEN origin = 'admin' THEN '🔐 Painel Admin'
        WHEN origin = 'public_store' THEN '🛍️ Loja Pública'
    END as origem_visual
FROM orders
ORDER BY created_at DESC;
```

### Exemplo de Resultado
```
| identify | origin        | origem_visual    | total   | created_at          |
|----------|---------------|------------------|---------|---------------------|
| ABC123   | admin         | 🔐 Painel Admin  | R$150.00| 2025-10-06 10:00:00 |
| XYZ789   | public_store  | 🛍️ Loja Pública | R$ 45.00| 2025-10-06 11:30:00 |
```

---

## 🌐 DESCOBRIR O SLUG DA LOJA

### Opção 1: Via Tinker
```bash
cd backend
php artisan tinker
```
```php
App\Models\Tenant::all(['id', 'name', 'slug']);

// Resultado:
// [
//   { id: 1, name: "Empresa Dev", slug: "empresa-dev" },
//   { id: 2, name: "Restaurante XYZ", slug: "restaurante-xyz" }
// ]
```

### Opção 2: Via SQL
```sql
SELECT id, name, slug, is_active 
FROM tenants 
WHERE is_active = 1;
```

### Opção 3: Via API
```bash
curl http://localhost:8000/api/store/empresa-dev/info | jq '.data.slug'
```

---

## 📱 FLUXOS COMPLETOS

### 1️⃣ Admin Criando Pedido
```
URL: http://localhost:3000/sign-in
  ↓
Login: user@email.com / senha
  ↓
URL: http://localhost:3000/orders
  ↓
Clicar: "Novo Pedido"
  ↓
Preencher formulário
  ↓
Salvar → origin = 'admin' ✅
```

### 2️⃣ Cliente na Loja (Guest - Sem Cadastro)
```
URL: http://localhost:3000/store/empresa-dev
  ↓
Navegar produtos
  ↓
Adicionar ao carrinho
  ↓
Checkout (sem fornecer senha)
  ↓
Finalizar → origin = 'public_store' ✅
  ↓
Cliente criado SEM senha
```

### 3️⃣ Cliente na Loja (Com Cadastro)
```
URL: http://localhost:3000/store/empresa-dev
  ↓
Navegar produtos
  ↓
Adicionar ao carrinho
  ↓
Checkout (fornecer senha no campo)
  ↓
Finalizar → origin = 'public_store' ✅
  ↓
Cliente criado COM senha ✅
  ↓
Pode fazer login depois!
```

### 4️⃣ Cliente Logado na Loja
```
URL: http://localhost:3000/store/empresa-dev/login
  ↓
Login: cliente@email.com / senha
  ↓
Token salvo no localStorage
  ↓
Dados pré-preenchidos no checkout
  ↓
Finalizar pedido rápido → origin = 'public_store' ✅
```

---

## 🔑 CREDENCIAIS DE TESTE

### Admin (Painel)
```
URL: http://localhost:3000/sign-in
Email: fabio@fabio.com
Senha: 123456
```

### Cliente (Loja Pública)
```
URL: http://localhost:3000/store/empresa-dev/login
Email: (cliente cadastrado)
Senha: (senha definida no registro)
```

---

## 🚀 QUICK START

### Acessar como Admin:
1. Abrir: http://localhost:3000/sign-in
2. Login: fabio@fabio.com / 123456
3. Dashboard: http://localhost:3000/dashboard

### Acessar como Cliente (Loja):
1. Abrir: http://localhost:3000/store/empresa-dev
2. Navegar e comprar (não precisa login)
3. Opcional: Criar conta fornecendo senha no checkout

### Testar Autenticação de Cliente:
```bash
# Executar script de teste
./test-client-auth.sh
```

---

## 📊 RESUMO VISUAL

```
┌─────────────────────────────────────────────────────────────┐
│                    SISTEMA MODAY                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  🔐 ADMIN                    🛍️ LOJA PÚBLICA               │
│  localhost:3000              localhost:3000/store/{slug}    │
│                                                              │
│  ┌─────────────────┐        ┌─────────────────┐            │
│  │ Usuários        │        │ Clientes        │            │
│  │ (users table)   │        │ (clients table) │            │
│  └─────────────────┘        └─────────────────┘            │
│          │                           │                      │
│          ▼                           ▼                      │
│  ┌─────────────────┐        ┌─────────────────┐            │
│  │ Cria Pedido     │        │ Cria Pedido     │            │
│  │ origin: admin   │        │ origin: public  │            │
│  └─────────────────┘        └─────────────────┘            │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## ✅ STATUS

- ✅ Admin funcionando em `http://localhost:3000`
- ✅ Loja pública funcionando em `http://localhost:3000/store/{slug}`
- ✅ Campo `origin` rastreando pedidos
- ✅ Autenticação de clientes implementada
- ✅ Guest checkout mantido
- ✅ Todos os testes passando

**Sistema pronto para uso!** 🎉
