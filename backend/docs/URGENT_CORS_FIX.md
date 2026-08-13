# 🚨 CORREÇÃO URGENTE DE CORS - SERVIDOR DE PRODUÇÃO

## ❌ Problema Atual
O servidor de produção está retornando `Access-Control-Allow-Origin: *` que é **INCOMPATÍVEL** com `credentials: 'include'`.

**Teste confirmado:**
```bash
curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Origin: http://localhost:3000" \
  -v
```

**Resultado atual (INCORRETO):**
```
access-control-allow-origin: *
```

## ✅ Solução Imediata

### 1. Acesse o servidor de produção
```bash
ssh seu-usuario@seu-servidor-digitalocean
cd /caminho/para/seu/projeto
```

### 2. Execute o script de correção
```bash
./fix-cors-production.sh
```

### 3. OU aplique manualmente:

#### Editar `config/cors.php`:
```php
'allowed_origins' => array_filter([
    'http://localhost:3000',
    'http://localhost:3001',
    'https://localhost:3000',
    'https://localhost:3001',
    'https://moday-nine.vercel.app',  // ← ADICIONAR
    env('FRONTEND_URL'),
    env('ADDITIONAL_CORS_ORIGIN'),
]),
```

#### Editar `app/Http/Middleware/CustomCorsMiddleware.php`:
```php
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'https://localhost:3000',
    'https://localhost:3001',
    'https://moday-nine.vercel.app',  // ← ADICIONAR
];
```

#### Editar `.env`:
```env
FRONTEND_URL=https://moday-nine.vercel.app
ADDITIONAL_CORS_ORIGIN=http://localhost:3000
```

### 4. Comandos obrigatórios:
```bash
# Limpar cache
php artisan config:clear
php artisan config:cache

# Reiniciar servidor web
sudo systemctl restart nginx
# OU
sudo systemctl restart apache2
```

### 5. Verificar se funcionou:
```bash
curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: POST" \
  -v
```

**Resultado esperado (CORRETO):**
```
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
```

## 🔍 Troubleshooting

Se ainda não funcionar:

1. **Verificar se não há cache do servidor web:**
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

2. **Verificar se o arquivo .env está sendo lido:**
   ```bash
   php artisan config:show cors
   ```

3. **Verificar logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## ⚠️ IMPORTANTE
- **NUNCA** usar `'*'` quando `supports_credentials: true`
- Sempre especificar domínios exatos
- Reiniciar servidor web após mudanças
- Limpar cache de configuração

## 📞 Status
- ❌ Servidor de produção: **NÃO FUNCIONANDO** (retorna `*`)
- ✅ Configuração local: **PRONTA** (arquivos atualizados)
- 🔄 **AÇÃO NECESSÁRIA:** Aplicar mudanças no servidor de produção
