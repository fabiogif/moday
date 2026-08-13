# 📋 Como Acessar Logs do Laravel em Produção

## 🔍 Logs Atuais (Servidor Web)
Você está vendo os logs do **nginx/apache**:
```
Oct 12 19:20:50  10.244.39.211 - - [12/Oct/2025:19:20:50 +0000] "OPTIONS /api/auth/login HTTP/1.1" 200 490
```

Isso mostra que:
- ✅ Requisição OPTIONS chegou ao servidor
- ✅ Retornou status 200 (não 204 como esperado)
- ✅ Origin: `https://clownfish-app-rr5rv.ondigitalocean.app`

## 🎯 Precisamos Ver os Logs do Laravel

### Opção 1: Via Console DigitalOcean (Recomendado)

1. **Acessar o Console:**
   - Painel DigitalOcean → App Platform
   - Selecione sua aplicação
   - Vá em **Console** (aba)

2. **Ver Logs do Laravel:**
```bash
# Ver logs recentes
tail -n 50 storage/logs/laravel.log

# Monitorar logs em tempo real
tail -f storage/logs/laravel.log

# Filtrar apenas logs CORS
tail -f storage/logs/laravel.log | grep "CORS Debug"
```

### Opção 2: Via SSH (se disponível)

```bash
# Conectar via SSH
ssh deploy@seu-app.digitalocean.com

# Ver logs
tail -f /var/www/html/storage/logs/laravel.log | grep "CORS Debug"
```

### Opção 3: Via Logs do App Platform

1. **No painel DigitalOcean:**
   - App Platform → Sua App → **Logs** (aba)
   - Filtrar por: `CORS Debug`

## 🧪 Teste para Gerar Logs

### 1. Fazer uma Requisição:
```bash
curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -v
```

### 2. Imediatamente Verificar Logs:
```bash
# No console DigitalOcean
tail -n 10 storage/logs/laravel.log | grep "CORS Debug"
```

## 🔍 O que Procurar nos Logs

### ✅ Log Esperado:
```json
[2024-10-12 19:20:50] production.INFO: CORS Debug {
    "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
    "method": "OPTIONS",
    "path": "api/auth/login",
    "allowed_origins": [...],
    "allowed": true,
    "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...",
    "request_id": "cors_64f8b9c2a1234"
}
```

### ❌ Se não aparecer:
- Middleware não está sendo executado
- Logs não estão sendo escritos
- Problema de configuração

## 🚨 Possíveis Problemas

### 1. Logs não aparecem:
```bash
# Verificar se arquivo de log existe
ls -la storage/logs/

# Verificar permissões
ls -la storage/logs/laravel.log

# Verificar se Laravel está escrevendo logs
tail -f storage/logs/laravel.log
```

### 2. Middleware não executando:
- Verificar se está registrado no `bootstrap/app.php`
- Verificar se não há erro 500 antes do middleware

### 3. Status 200 em vez de 204:
- Pode ser cache do nginx
- Ou outro middleware interferindo

## 📋 Checklist de Debug

### 1. Acessar Console DigitalOcean:
- [ ] App Platform → Sua App → Console
- [ ] Executar: `tail -f storage/logs/laravel.log`

### 2. Fazer Requisição de Teste:
- [ ] OPTIONS para `/api/auth/login`
- [ ] POST para `/api/auth/login`
- [ ] Verificar se logs aparecem

### 3. Analisar Logs:
- [ ] Origin está correto?
- [ ] Allowed = true?
- [ ] Method correto?
- [ ] Headers sendo adicionados?

## 🎯 Próximos Passos

1. **Acessar console DigitalOcean**
2. **Executar**: `tail -f storage/logs/laravel.log | grep "CORS Debug"`
3. **Fazer requisição** do frontend ou curl
4. **Analisar logs** para identificar problema
5. **Corrigir** baseado nos logs

---

**💡 Dica**: Os logs do servidor web mostram que a requisição está chegando, agora precisamos ver os logs do Laravel para entender o que está acontecendo no middleware CORS!
