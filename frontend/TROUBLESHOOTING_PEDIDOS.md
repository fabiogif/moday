# 🐛 Troubleshooting - Erro ao Criar Pedido

Guia para resolver o erro "Load failed" ao realizar pedidos pelo cardápio.

---

## ❌ Erro: "Load failed" ao Finalizar Pedido

### Sintomas

- Ao clicar em "Finalizar Pedido" no cardápio
- Aparece erro "Load failed" ou "Failed to fetch"
- Console mostra erro de rede
- Pedido não é criado

---

## 🔍 Causas Possíveis

### 1️⃣ Backend Não Está Rodando

**Verificar:**
```bash
# Verificar se o backend está rodando
curl http://localhost:8000/api/health

# Ou na URL de produção
curl https://orca-app-7hejo.ondigitalocean.app/api/health
```

**Solução:**
```bash
# Iniciar backend localmente
cd backend
php artisan serve

# Ou via Docker
docker-compose up
```

---

### 2️⃣ URL do Backend Incorreta

**Verificar:**
1. Abra o console do navegador (F12)
2. Na aba "Network", tente fazer um pedido
3. Verifique a URL da requisição

**Solução:**

Verifique a variável de ambiente `NEXT_PUBLIC_API_URL`:

```bash
# Frontend .env.local
NEXT_PUBLIC_API_URL=https://orca-app-7hejo.ondigitalocean.app
```

**Opções válidas:**
- Desenvolvimento: `http://localhost:8000`
- Produção Digital Ocean: `https://orca-app-7hejo.ondigitalocean.app`

---

### 3️⃣ Problema de CORS

**Sintomas:**
- Console mostra erro CORS
- Mensagem: "blocked by CORS policy"

**Verificar no Console:**
```
Access to fetch at 'https://...' from origin 'https://...' has been blocked by CORS policy
```

**Solução:**

Certifique-se de que o domínio do frontend está configurado no backend:

```php
// backend/app/Http/Middleware/GlobalCorsMiddleware.php

$allowedOrigins = [
    'http://localhost:3000',
    'https://moday-frontend.vercel.app',  // ✅ Adicione seu domínio
    'https://orca-app-7hejo.ondigitalocean.app',
];
```

Depois, faça deploy do backend:
```bash
git add .
git commit -m "fix: Add frontend domain to CORS"
git push origin main
```

---

### 4️⃣ Endpoint Não Existe

**Verificar:**

Teste o endpoint diretamente:

```bash
curl -X POST https://orca-app-7hejo.ondigitalocean.app/api/store/seu-slug/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "client": {
      "name": "Test",
      "email": "test@test.com",
      "phone": "1234567890"
    },
    "delivery": {
      "is_delivery": false
    },
    "products": [
      {
        "uuid": "produto-uuid",
        "quantity": 1
      }
    ],
    "payment_method": "payment-uuid",
    "shipping_method": "pickup"
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Pedido criado com sucesso",
  "data": { ... }
}
```

---

### 5️⃣ Dados Inválidos

**Verificar:**

Abra o console (F12) e procure por:
```
=== DEBUG: Order Data Being Sent ===
```

**Campos obrigatórios:**
- `client.name` ✅
- `client.email` ✅
- `client.phone` ✅
- `products` (array com pelo menos 1 produto) ✅
- `payment_method` (UUID válido) ✅
- `shipping_method` ("delivery" ou "pickup") ✅

**Para delivery, também são obrigatórios:**
- `delivery.address`
- `delivery.number`
- `delivery.neighborhood`
- `delivery.city`
- `delivery.state`
- `delivery.zip_code`

---

## 🛠️ Como Debugar

### Passo 1: Abrir Console do Navegador

1. Pressione F12
2. Vá na aba "Console"
3. Tente fazer um pedido

### Passo 2: Verificar Logs de Debug

Você verá logs como:

```javascript
=== DEBUG: Order Data Being Sent ===
shippingMethod: "delivery"
deliveryDataToSend: { ... }
full orderData: { ... }

=== DEBUG: Request Info ===
API URL: https://orca-app-7hejo.ondigitalocean.app
Full URL: https://orca-app-7hejo.ondigitalocean.app/api/store/minha-loja/orders
Slug: minha-loja
```

### Passo 3: Verificar Network

1. Vá na aba "Network" do DevTools
2. Filtre por "XHR" ou "Fetch"
3. Tente fazer o pedido
4. Clique na requisição que falhou
5. Veja:
   - **Status**: Deve ser 200 ou 201
   - **Response**: Veja a resposta do servidor
   - **Headers**: Verifique CORS

---

## ✅ Soluções Rápidas

### Solução 1: Verificar Variáveis de Ambiente

**Frontend (`frontend/.env.local`):**
```env
NEXT_PUBLIC_API_URL=https://orca-app-7hejo.ondigitalocean.app
```

**Reiniciar servidor:**
```bash
# Parar servidor (Ctrl+C)
# Iniciar novamente
npm run dev
```

---

### Solução 2: Atualizar CORS no Backend

```bash
cd backend

# Editar middleware
nano app/Http/Middleware/GlobalCorsMiddleware.php

# Adicionar domínio do frontend
# $allowedOrigins = [
#     'https://seu-frontend.vercel.app',  # ← Adicione aqui
# ];

# Commit e push
git add .
git commit -m "fix: Update CORS origins"
git push
```

---

### Solução 3: Limpar Cache

```bash
# Frontend
rm -rf .next
npm run dev

# Backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

### Solução 4: Verificar Firewall

Se estiver em produção, verifique:

1. **Firewall do servidor** permite conexões na porta 80/443
2. **CDN/Proxy** (se houver) está configurado corretamente
3. **SSL/TLS** está ativo e válido

---

## 🧪 Testar Localmente

Para testar localmente e isolar o problema:

### Terminal 1 - Backend
```bash
cd backend
php artisan serve
# Rodando em http://localhost:8000
```

### Terminal 2 - Frontend
```bash
cd frontend

# Criar .env.local
echo "NEXT_PUBLIC_API_URL=http://localhost:8000" > .env.local

# Iniciar
npm run dev
# Rodando em http://localhost:3000
```

### Testar
1. Abra http://localhost:3000/store/seu-slug
2. Adicione produtos ao carrinho
3. Tente finalizar pedido
4. Verifique console para erros

---

## 📊 Logs Detalhados

Com as melhorias implementadas, você verá logs detalhados:

### Console do Frontend

```javascript
=== DEBUG: Order Data Being Sent ===
shippingMethod: delivery
deliveryDataToSend: {...}

=== DEBUG: Request Info ===
API URL: https://...
Full URL: https://.../api/store/slug/orders

=== DEBUG: Backend Response ===
response status: 201
response result: {...}
```

### Se Houver Erro

```javascript
=== ERROR creating order ===
Error type: TypeError
Network error - possible causes:
1. Backend is not running
2. CORS is not configured properly
3. Wrong API_URL: undefined
```

---

## 🔄 Processo de Debug Completo

### 1. Confirmar Backend Está Online

```bash
curl https://orca-app-7hejo.ondigitalocean.app/api/health
```

✅ **Esperado:**
```json
{"status":"ok","timestamp":"..."}
```

### 2. Confirmar Slug da Loja Existe

```bash
curl https://orca-app-7hejo.ondigitalocean.app/api/store/seu-slug/info
```

✅ **Esperado:**
```json
{"success":true,"data":{"name":"...","slug":"seu-slug"}}
```

### 3. Confirmar Produtos Existem

```bash
curl https://orca-app-7hejo.ondigitalocean.app/api/store/seu-slug/products
```

✅ **Esperado:**
```json
{"success":true,"data":[...]}
```

### 4. Testar Criar Pedido

Use o comando curl completo mostrado na seção "4️⃣ Endpoint Não Existe"

---

## 📞 Quando Pedir Ajuda

Se após todas as tentativas o erro persistir, forneça:

1. **Logs do Console** (F12 → Console)
2. **Request Headers** (F12 → Network → Requisição → Headers)
3. **Response** (F12 → Network → Requisição → Response)
4. **Versão do Node**: `node -v`
5. **Versão do npm**: `npm -v`
6. **Sistema Operacional**
7. **Navegador e versão**

---

## 🎯 Checklist Final

Antes de fazer deploy ou testar:

- [ ] Backend está rodando
- [ ] `NEXT_PUBLIC_API_URL` está correto
- [ ] Frontend pode acessar backend (teste com curl)
- [ ] CORS está configurado com domínio do frontend
- [ ] Rota `/api/store/{slug}/orders` existe
- [ ] Banco de dados está acessível
- [ ] Migrations foram executadas
- [ ] Loja com slug existe no banco
- [ ] Produtos existem e têm estoque
- [ ] Forma de pagamento está cadastrada

---

**Última atualização:** 2025-10-27  
**Versão:** 1.0.0
