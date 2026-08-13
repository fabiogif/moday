# 🔍 Logs de Debug do AuthController

## ✅ **Logs Implementados com Sucesso**

### **1. AuthController Logs:**
```json
// Requisição recebida
{
    "ip": "192.168.65.1",
    "user_agent": "curl/8.7.1", 
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "method": "POST",
    "content_type": "application/json",
    "request_id": "auth_68ec0291cffdd",
    "timestamp": "2025-10-12T19:33:37.851964Z"
}

// Credenciais validadas
{
    "email": "fabio@fabio.com",
    "has_password": true,
    "remember": true,
    "request_id": "auth_68ec0291d12e5"
}

// Login falhou
{
    "email": "fabio@fabio.com",
    "reason": "Credenciais inválidas",
    "ip": "192.168.65.1",
    "request_id": "auth_68ec02923b3c4"
}

// Erro de validação
{
    "errors": {"email": ["Credenciais inválidas"]},
    "ip": "192.168.65.1",
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "request_id": "auth_68ec02923c420"
}
```

### **2. AuthService Logs:**
```json
// Serviço chamado
{
    "email": "fabio@fabio.com"
}

// Usuário encontrado
{
    "found": true,
    "user_id": 123
}

// Verificação de senha
{
    "provided_password": "senha123",
    "stored_hash": "$2y$10$abc123...",
    "hash_check": true
}

// Login bem-sucedido
{
    "user_id": 123
}
```

## 🎯 **Como Monitorar em Produção**

### **1. Acessar Console DigitalOcean:**
```bash
# Ver logs em tempo real
tail -f storage/logs/laravel.log | grep "AuthController"

# Filtrar apenas logs de login
tail -f storage/logs/laravel.log | grep -E "(AuthController login|AuthService login)"
```

### **2. Filtrar por Tipo de Log:**
```bash
# Apenas requisições recebidas
tail -f storage/logs/laravel.log | grep "AuthController login called"

# Apenas falhas de login
tail -f storage/logs/laravel.log | grep "AuthController login failed"

# Apenas sucessos
tail -f storage/logs/laravel.log | grep "AuthController login successful"

# Apenas erros internos
tail -f storage/logs/laravel.log | grep "AuthController internal error"
```

### **3. Filtrar por IP ou Origin:**
```bash
# Por IP específico
tail -f storage/logs/laravel.log | grep "AuthController" | grep "192.168.65.1"

# Por origin específico
tail -f storage/logs/laravel.log | grep "AuthController" | grep "clownfish-app-rr5rv"
```

## 🔍 **Debug de Problemas CORS**

### **1. Fluxo Completo:**
```
1. CORS Debug (GlobalCorsMiddleware)
2. AuthController login called
3. AuthController credentials validated
4. AuthService login called
5. AuthController login successful/failed
```

### **2. Verificar Fluxo:**
```bash
# Ver todo o fluxo de uma requisição
tail -f storage/logs/laravel.log | grep -E "(CORS Debug|AuthController|AuthService)"
```

### **3. Identificar Problemas:**
```bash
# Se não aparecer "AuthController login called":
# - Middleware CORS está bloqueando
# - Rate limiting está bloqueando
# - Erro 500 antes do controller

# Se aparecer mas falhar na validação:
# - Problema com LoginRequest
# - Dados inválidos

# Se falhar no AuthService:
# - Usuário não encontrado
# - Senha incorreta
# - Usuário inativo
```

## 📊 **Logs de Sucesso vs Falha**

### **Login Bem-sucedido:**
```json
{
    "user_id": 123,
    "email": "user@example.com",
    "ip": "192.168.65.1",
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "request_id": "auth_68ec02923c420"
}
```

### **Login Falhou:**
```json
{
    "email": "user@example.com",
    "reason": "Credenciais inválidas",
    "ip": "192.168.65.1",
    "request_id": "auth_68ec02923b3c4"
}
```

## 🚨 **Alertas e Monitoramento**

### **1. Falhas de Login:**
```bash
# Contar falhas por hora
grep "AuthController login failed" storage/logs/laravel.log | wc -l

# Ver falhas recentes
grep "AuthController login failed" storage/logs/laravel.log | tail -10
```

### **2. Tentativas de Login:**
```bash
# Contar tentativas por IP
grep "AuthController login called" storage/logs/laravel.log | awk '{print $NF}' | sort | uniq -c
```

### **3. Origins Problemáticos:**
```bash
# Ver origins que mais falham
grep "AuthController login failed" storage/logs/laravel.log | grep -o 'origin":"[^"]*' | sort | uniq -c
```

## 🧪 **Teste Completo**

### **1. Fazer Requisição:**
```bash
curl -X POST https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -d '{"email": "test@example.com", "password": "password123", "remember": true}'
```

### **2. Monitorar Logs:**
```bash
# No console DigitalOcean
tail -f storage/logs/laravel.log | grep -E "(CORS Debug|AuthController|AuthService)"
```

### **3. Analisar Resultado:**
- ✅ CORS Debug aparece?
- ✅ AuthController login called aparece?
- ✅ Credenciais validadas?
- ✅ AuthService processou?
- ✅ Resultado final (success/failed)?

## 📋 **Checklist de Debug**

### **Problemas CORS:**
- [ ] CORS Debug aparece nos logs?
- [ ] Origin está na lista de permitidos?
- [ ] Headers CORS estão corretos?
- [ ] Status 204 para OPTIONS?

### **Problemas de Login:**
- [ ] AuthController login called aparece?
- [ ] Credenciais validadas?
- [ ] AuthService processou?
- [ ] Usuário existe no banco?
- [ ] Senha está correta?
- [ ] Usuário está ativo?

### **Problemas de Rate Limiting:**
- [ ] Rate limit headers aparecem?
- [ ] Muitas tentativas por IP?
- [ ] Throttle:login configurado?

## 🎯 **Próximos Passos**

1. **Deploy com logs ativos**
2. **Monitorar em produção**
3. **Identificar problema específico**
4. **Corrigir baseado nos logs**
5. **Remover logs após correção**

---

**💡 Os logs estão funcionando perfeitamente e vão ajudar a identificar exatamente onde está o problema no fluxo de login!**
