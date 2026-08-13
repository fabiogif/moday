# Configuração CORS para Produção

## Problema
O frontend em `https://moday-nine.vercel.app` está sendo bloqueado pelo CORS ao tentar acessar a API em `https://orca-app-7hejo.ondigitalocean.app/`.

## Solução

### 1. Arquivos que precisam ser atualizados no servidor de produção:

#### `config/cors.php`
```php
'allowed_origins' => array_filter([
    'http://localhost:3000',
    'http://localhost:3001',
    'https://localhost:3000',
    'https://localhost:3001',
    'https://moday-nine.vercel.app',  // ← ADICIONAR ESTA LINHA
    env('FRONTEND_URL'),
    env('ADDITIONAL_CORS_ORIGIN'),
]),
```

#### `app/Http/Middleware/CustomCorsMiddleware.php`
```php
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'https://localhost:3000',
    'https://localhost:3001',
    'https://moday-nine.vercel.app',  // ← ADICIONAR ESTA LINHA
];
```

#### `.env` (no servidor de produção)
```env
# CORS Configuration
FRONTEND_URL=https://moday-nine.vercel.app
ADDITIONAL_CORS_ORIGIN=http://localhost:3000
```

### 2. Comandos para executar no servidor de produção:

```bash
# 1. Fazer backup dos arquivos atuais
cp config/cors.php config/cors.php.backup
cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.backup

# 2. Aplicar as mudanças nos arquivos (usar git pull ou editar manualmente)

# 3. Limpar cache de configuração
php artisan config:clear
php artisan config:cache

# 4. Reiniciar o servidor web
sudo systemctl restart nginx  # ou apache2
# ou se estiver usando PHP-FPM:
sudo systemctl restart php8.1-fpm  # ajustar versão conforme necessário
```

### 3. Verificação:

Após aplicar as mudanças, testar com:

```bash
curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Origin: https://moday-nine.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  -v
```

Deve retornar headers como:
```
Access-Control-Allow-Origin: https://moday-nine.vercel.app
Access-Control-Allow-Credentials: true
```

### 4. Notas importantes:

- **NUNCA** usar `'*'` em `allowed_origins` quando `supports_credentials` for `true`
- O domínio deve ser exato (incluindo protocolo: `https://`)
- Após mudanças, sempre limpar o cache de configuração
- Reiniciar o servidor web após as alterações

### 5. Troubleshooting:

Se ainda houver problemas:
1. Verificar se o arquivo `.env` está sendo lido corretamente
2. Verificar se não há outros middlewares sobrescrevendo os headers CORS
3. Verificar logs do servidor web para erros
4. Usar ferramentas de desenvolvedor do navegador para verificar os headers de resposta
