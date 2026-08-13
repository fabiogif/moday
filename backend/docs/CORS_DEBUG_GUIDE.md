# 🔍 Guia de Debug CORS com Logs

## 📝 Logs Adicionados

Adicionados logs temporários no `GlobalCorsMiddleware` para debug:

```php
// LOG para debug - remova depois
\Log::info('CORS Debug', [
    'origin' => $origin,
    'method' => $request->getMethod(),
    'path' => $request->path(),
    'allowed_origins' => $allowedOrigins,
    'allowed' => in_array($origin, $allowedOrigins),
    'user_agent' => $request->header('User-Agent'),
    'request_id' => uniqid('cors_')
]);
```

## 🔍 Como Monitorar os Logs

### 1. Em Desenvolvimento (Local)

```bash
# Monitorar logs em tempo real
tail -f storage/logs/laravel.log | grep "CORS Debug"

# Ou filtrar apenas logs CORS
tail -f storage/logs/laravel.log | grep -A 10 -B 2 "CORS Debug"
```

### 2. Em Produção (DigitalOcean)

#### Via Console DigitalOcean:
```bash
# Acessar o console da aplicação
# No painel DigitalOcean → App → Console

# Monitorar logs
tail -f storage/logs/laravel.log | grep "CORS Debug"

# Ver logs recentes
tail -n 100 storage/logs/laravel.log | grep "CORS Debug"
```

#### Via SSH (se disponível):
```bash
# Conectar via SSH
ssh deploy@seu-app.digitalocean.com

# Monitorar logs
tail -f /var/www/html/storage/logs/laravel.log | grep "CORS Debug"
```

## 🧪 Como Testar e Ver os Logs

### 1. Teste de Preflight (OPTIONS)

```bash
curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  -v
```

**Log esperado:**
```json
[2024-01-XX XX:XX:XX] local.INFO: CORS Debug {
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "method": "OPTIONS",
    "path": "api/auth/login",
    "allowed_origins": [
        "http://localhost:3000",
        "http://localhost:3001",
        "https://localhost:3000",
        "https://localhost:3001",
        "https://moday-nine.vercel.app",
        "https://clownfish-app-rr5rv.ondigitalocean.app"
    ],
    "allowed": true,
    "user_agent": "curl/8.7.1",
    "request_id": "cors_64f8b9c2a1234"
}
```

### 2. Teste de Requisição Real (POST)

```bash
curl -X POST https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

**Log esperado:**
```json
[2024-01-XX XX:XX:XX] local.INFO: CORS Debug {
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "method": "POST",
    "path": "api/auth/login",
    "allowed_origins": [...],
    "allowed": true,
    "user_agent": "curl/8.7.1",
    "request_id": "cors_64f8b9c2a5678"
}
```

## 🔍 O que Procurar nos Logs

### ✅ Cenário de Sucesso:
- `"allowed": true`
- `origin` está na lista `allowed_origins`
- `method` é `OPTIONS` ou `POST/GET`

### ❌ Cenário de Problema:
- `"allowed": false`
- `origin` é `null` ou não está na lista
- `allowed_origins` está vazio ou incompleto

### 🚨 Possíveis Problemas:

#### 1. Origin não está na lista:
```json
{
    "origin": "https://outro-dominio.com",
    "allowed": false,
    "allowed_origins": ["https://clownfish-app-rr5rv.ondigitalocean.app"]
}
```
**Solução**: Adicionar o domínio em `$allowedOrigins`

#### 2. Origin é null:
```json
{
    "origin": null,
    "allowed": false
}
```
**Solução**: Verificar se o frontend está enviando o header `Origin`

#### 3. Variáveis de ambiente não carregadas:
```json
{
    "allowed_origins": [
        "http://localhost:3000",
        null,
        null
    ]
}
```
**Solução**: Verificar `env('FRONTEND_URL')` e `env('ADDITIONAL_CORS_ORIGIN')`

## 📋 Checklist de Debug

### Antes de Testar:
- [ ] Logs ativados no middleware
- [ ] Deploy feito em produção
- [ ] Cache limpo

### Durante o Teste:
- [ ] Monitorar logs em tempo real
- [ ] Fazer requisição OPTIONS
- [ ] Fazer requisição POST/GET
- [ ] Verificar se `allowed: true`

### Análise dos Logs:
- [ ] Origin está correto?
- [ ] Allowed origins incluem o frontend?
- [ ] Method é o esperado?
- [ ] Request ID único gerado?

## 🧹 Remover Logs Após Debug

### 1. Editar o Middleware:
```php
// Remover estas linhas do GlobalCorsMiddleware.php:
\Log::info('CORS Debug', [
    'origin' => $origin,
    'method' => $request->getMethod(),
    'path' => $request->path(),
    'allowed_origins' => $allowedOrigins,
    'allowed' => in_array($origin, $allowedOrigins),
    'user_agent' => $request->header('User-Agent'),
    'request_id' => uniqid('cors_')
]);
```

### 2. Commitar e Deploy:
```bash
git add app/Http/Middleware/GlobalCorsMiddleware.php
git commit -m "remove: logs temporários de debug CORS"
git push origin main
```

## 📊 Exemplo de Logs Completos

### Requisição OPTIONS (Preflight):
```json
[2024-01-12 15:30:45] production.INFO: CORS Debug {
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "method": "OPTIONS",
    "path": "api/auth/login",
    "allowed_origins": [
        "http://localhost:3000",
        "http://localhost:3001",
        "https://localhost:3000",
        "https://localhost:3001",
        "https://moday-nine.vercel.app",
        "https://clownfish-app-rr5rv.ondigitalocean.app"
    ],
    "allowed": true,
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "request_id": "cors_64f8b9c2a1234"
}
```

### Requisição POST (Login):
```json
[2024-01-12 15:30:46] production.INFO: CORS Debug {
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "method": "POST",
    "path": "api/auth/login",
    "allowed_origins": [...],
    "allowed": true,
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "request_id": "cors_64f8b9c2a5678"
}
```

## 🎯 Próximos Passos

1. **Deploy com logs**: Fazer deploy para ver os logs em produção
2. **Monitorar**: Acompanhar logs durante requisições do frontend
3. **Identificar problema**: Analisar se origin está sendo permitida
4. **Corrigir**: Ajustar configuração se necessário
5. **Remover logs**: Limpar logs temporários após debug

---

**⚠️ IMPORTANTE**: Lembre-se de remover os logs após identificar e corrigir o problema para não poluir os logs de produção!
