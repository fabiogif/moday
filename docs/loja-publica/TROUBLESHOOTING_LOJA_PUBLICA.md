# 🔧 Troubleshooting - Loja Pública

## ❌ Erro: "Unexpected token '<', "<!DOCTYPE "... is not valid JSON"

### Causa
A API Laravel está retornando HTML em vez de JSON. Isso acontece quando:
1. O servidor Laravel não está rodando
2. A URL da API está incorreta
3. As rotas não estão registradas
4. Há um erro 404 ou 500 no backend

---

## ✅ Soluções

### 1. Verificar se o Servidor Laravel está Rodando

```bash
# Terminal 1 - Backend
cd backend
php artisan serve
```

**Saída esperada:**
```
Laravel development server started: http://127.0.0.1:8000
```

### 2. Verificar URL da API

**Arquivo:** `frontend/.env.local`

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

**Importante:** 
- ✅ Correto: `http://localhost:8000` (com porta)
- ❌ Errado: `http://localhost` (sem porta)

### 3. Testar Endpoints Diretamente

```bash
# Testar endpoint de info
curl http://localhost:8000/api/store/empresa-dev/info

# Testar endpoint de produtos
curl http://localhost:8000/api/store/empresa-dev/products
```

**Resposta esperada (JSON):**
```json
{
  "success": true,
  "data": {...}
}
```

### 4. Verificar Rotas do Laravel

```bash
cd backend
php artisan route:list | grep store
```

**Saída esperada:**
```
GET|HEAD  api/store/{slug}/info ............. PublicStoreController@getStoreInfo
GET|HEAD  api/store/{slug}/products ........ PublicStoreController@getProducts
POST      api/store/{slug}/orders .......... PublicStoreController@createOrder
```

### 5. Limpar Cache do Laravel

```bash
cd backend
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## 🐛 Outros Erros Comuns

### Erro: "Loja não encontrada"

**Causa:** Tenant não tem slug ou está inativo

**Solução:**
```sql
-- Verificar tenant
SELECT id, name, slug, is_active FROM tenants WHERE id = 1;

-- Adicionar slug
UPDATE tenants SET slug = 'minha-loja' WHERE id = 1;

-- Ativar tenant
UPDATE tenants SET is_active = TRUE WHERE id = 1;
```

### Erro: "Produtos não aparecem"

**Causa:** Produtos inativos ou sem estoque

**Solução:**
```sql
-- Verificar produtos
SELECT id, name, tenant_id, qtd_stock, is_active 
FROM products 
WHERE tenant_id = 1;

-- Ativar produtos
UPDATE products 
SET is_active = TRUE, qtd_stock = 10 
WHERE tenant_id = 1;
```

### Erro: CORS

**Causa:** CORS não configurado para rotas públicas

**Solução:**

**Arquivo:** `backend/config/cors.php`
```php
'paths' => [
    'api/*',
    'sanctum/csrf-cookie',
    'broadcasting/auth',
],
```

### Erro: "Class PublicStoreController not found"

**Causa:** Namespace incorreto ou arquivo não existe

**Solução:**
```bash
# Verificar se arquivo existe
ls -la backend/app/Http/Controllers/Api/PublicStoreController.php

# Recriar autoload
cd backend
composer dump-autoload
```

---

## ✅ Checklist de Validação

Antes de acessar a loja, verifique:

### Backend
- [ ] Servidor Laravel rodando (`php artisan serve`)
- [ ] Migration executada (`php artisan migrate`)
- [ ] PublicStoreController existe
- [ ] Rotas registradas em `api.php`
- [ ] Tenant tem slug
- [ ] Tenant está ativo
- [ ] Produtos existem e estão ativos
- [ ] Produtos têm estoque > 0

### Frontend
- [ ] `.env.local` tem `NEXT_PUBLIC_API_URL=http://localhost:8000`
- [ ] Frontend rodando (`npm run dev`)
- [ ] URL correta: `/store/{slug}`
- [ ] Console sem erros

### Testes de Endpoints
- [ ] `curl http://localhost:8000/api/store/{slug}/info` retorna JSON
- [ ] `curl http://localhost:8000/api/store/{slug}/products` retorna JSON
- [ ] Status 200 (não 404 ou 500)

---

## 🔍 Debug Passo a Passo

### 1. Verificar Backend

```bash
# Terminal 1
cd backend
php artisan serve
```

Abra: `http://localhost:8000`
- ✅ Deve mostrar página do Laravel
- ❌ Se não abrir, há problema no servidor

### 2. Testar Endpoint Manualmente

```bash
# Em outro terminal
curl -v http://localhost:8000/api/store/empresa-dev/info
```

**Se retornar HTML:**
- Rota não existe
- Controller não foi encontrado
- Erro 500 no backend

**Se retornar JSON:**
- ✅ Backend está OK
- Problema é no frontend

### 3. Verificar Frontend

**Arquivo:** `frontend/.env.local`
```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

**Console do navegador:**
```javascript
// Deve mostrar a URL completa
console.log(process.env.NEXT_PUBLIC_API_URL)
// Saída: http://localhost:8000
```

### 4. Verificar Rede (DevTools)

1. Abra DevTools (F12)
2. Aba Network
3. Recarregue a página
4. Veja as requisições para `/api/store/...`
5. Verifique:
   - Status Code (deve ser 200)
   - Response Type (deve ser JSON)
   - Response Content (deve ter `success: true`)

---

## 📋 Comandos de Diagnóstico

### Verificar Tudo de Uma Vez

```bash
#!/bin/bash

echo "=== DIAGNÓSTICO DA LOJA PÚBLICA ==="

# 1. Verificar se backend está rodando
echo -e "\n1. Testando Backend..."
curl -s http://localhost:8000 > /dev/null && echo "✅ Backend OK" || echo "❌ Backend não está rodando"

# 2. Testar endpoint de info
echo -e "\n2. Testando Endpoint Info..."
RESPONSE=$(curl -s http://localhost:8000/api/store/empresa-dev/info)
if echo "$RESPONSE" | grep -q "success"; then
    echo "✅ Endpoint Info OK"
else
    echo "❌ Endpoint Info falhou"
    echo "Resposta: $RESPONSE"
fi

# 3. Verificar tenant no banco
echo -e "\n3. Verificando Tenant..."
cd backend
php artisan tinker --execute="echo \App\Models\Tenant::where('slug', 'empresa-dev')->exists() ? '✅ Tenant encontrado' : '❌ Tenant não encontrado';"

# 4. Verificar produtos
echo -e "\n4. Verificando Produtos..."
php artisan tinker --execute="echo \App\Models\Product::where('is_active', true)->where('qtd_stock', '>', 0)->count() . ' produtos disponíveis';"

echo -e "\n=== FIM DO DIAGNÓSTICO ==="
```

Salve como `diagnostico.sh` e execute:
```bash
chmod +x diagnostico.sh
./diagnostico.sh
```

---

## 🚑 Soluções Rápidas

### Problema: Backend não inicia

```bash
# Verificar porta em uso
lsof -i :8000

# Matar processo
kill -9 PID

# Reiniciar
php artisan serve
```

### Problema: Frontend não conecta

```bash
# Recriar .env.local
cat > frontend/.env.local << EOF
NEXT_PUBLIC_API_URL=http://localhost:8000
EOF

# Reiniciar frontend
npm run dev
```

### Problema: Rotas não funcionam

```bash
cd backend

# Limpar tudo
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Recriar autoload
composer dump-autoload

# Listar rotas
php artisan route:list | grep store
```

---

## 📞 Ajuda Adicional

Se o problema persistir:

1. **Verifique logs do Laravel:**
   ```bash
   tail -f backend/storage/logs/laravel.log
   ```

2. **Verifique console do navegador:**
   - F12 → Console
   - Procure por erros em vermelho

3. **Verifique aba Network:**
   - F12 → Network
   - Veja a requisição que falhou
   - Analise Request/Response

4. **Teste com Postman/Insomnia:**
   - GET `http://localhost:8000/api/store/empresa-dev/info`
   - Se funcionar no Postman mas não no frontend, é problema de CORS ou .env

---

## ✅ Tudo Funcionando?

Quando tudo estiver OK, você deve ver:

### Backend (Terminal)
```
Laravel development server started: http://127.0.0.1:8000
[200] GET /api/store/empresa-dev/info
[200] GET /api/store/empresa-dev/products
```

### Frontend (Console)
```
Navegando para: /store/empresa-dev
API URL: http://localhost:8000
Store loaded successfully
```

### Navegador
```
✅ Loja carrega
✅ Produtos aparecem
✅ Sem erros no console
```

---

**Problema resolvido?** ✅  
**Loja funcionando?** 🚀  
**Pedidos sendo criados?** 🎉
