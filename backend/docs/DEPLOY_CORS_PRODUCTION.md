# Deploy de Correção CORS para Produção

## 📋 Resumo das Alterações

### Arquivos Modificados

1. **bootstrap/app.php**
   - Configurado middleware `CustomCorsMiddleware` para rotas API
   - Removido conflito com middleware HandleCors padrão do Laravel

2. **app/Http/Middleware/CustomCorsMiddleware.php**
   - Implementado middleware CORS customizado
   - Configurado para permitir origens específicas:
     - `http://localhost:3000`
     - `http://localhost:3001`
     - `https://localhost:3000`
     - `https://localhost:3001`
     - `https://moday-nine.vercel.app` (PRODUÇÃO)
   - Headers configurados:
     - Access-Control-Allow-Credentials: true
     - Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH
     - Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN
     - Access-Control-Max-Age: 86400 (24 horas)

## 🚀 Status do Deploy

✅ Código local atualizado e testado
✅ Alterações commitadas no Git
✅ Código sincronizado com repositório remoto (GitHub)
⏳ Aguardando deploy no servidor de produção

## 📝 Instruções para Deploy no Servidor

### 1. Conectar ao Servidor DigitalOcean

```bash
ssh root@orca-app-7hejo.ondigitalocean.app
```

### 2. Navegar para o Diretório da Aplicação

```bash
cd /var/www/html
```

### 3. Fazer Backup dos Arquivos Atuais

```bash
cp bootstrap/app.php bootstrap/app.php.bak.$(date +%Y%m%d_%H%M%S)
cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.bak.$(date +%Y%m%d_%H%M%S)
```

### 4. Atualizar Código do Repositório

```bash
git pull origin main
```

### 5. Limpar Todos os Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 6. Recriar Cache de Configuração

```bash
php artisan config:cache
```

### 7. Ajustar Permissões

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 8. Reiniciar Serviços

```bash
# Verificar versão do PHP
php -v

# Reiniciar PHP-FPM (ajuste a versão conforme necessário)
systemctl restart php8.3-fpm  # ou php8.2-fpm

# Reiniciar Nginx
systemctl restart nginx
```

## 🧪 Testes Pós-Deploy

### Teste 1: Verificar Headers CORS

```bash
curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/login \
  -H "Origin: https://moday-nine.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  -v
```

**Resultado esperado:**
```
< HTTP/1.1 200 OK
< Access-Control-Allow-Origin: https://moday-nine.vercel.app
< Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH
< Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN
< Access-Control-Allow-Credentials: true
< Access-Control-Max-Age: 86400
```

### Teste 2: Verificar Requisição POST

```bash
curl -X POST https://orca-app-7hejo.ondigitalocean.app/api/login \
  -H "Origin: https://moday-nine.vercel.app" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  -v
```

### Teste 3: Testar Login no Frontend

1. Acessar: https://moday-nine.vercel.app
2. Tentar fazer login com credenciais válidas
3. Verificar no console do navegador se há erros CORS
4. Confirmar que a requisição é bem-sucedida

## 🔍 Verificação de Logs

### Logs da Aplicação Laravel

```bash
tail -f /var/www/html/storage/logs/laravel.log
```

### Logs do Nginx

```bash
# Logs de erro
tail -f /var/log/nginx/error.log

# Logs de acesso
tail -f /var/log/nginx/access.log
```

### Logs do PHP-FPM

```bash
tail -f /var/log/php8.3-fpm.log  # Ajuste a versão conforme necessário
```

## ⚙️ Configurações de Ambiente (.env)

Verificar se o arquivo `.env` de produção contém as seguintes variáveis:

```env
APP_URL=https://orca-app-7hejo.ondigitalocean.app
FRONTEND_URL=https://moday-nine.vercel.app
SANCTUM_STATEFUL_DOMAINS=moday-nine.vercel.app
SESSION_DOMAIN=.orca-app-7hejo.ondigitalocean.app
SESSION_SECURE_COOKIE=true
```

## 📋 Checklist de Verificação

- [ ] Backup dos arquivos atuais criado
- [ ] Código atualizado do repositório (git pull)
- [ ] Caches limpos (config, cache, route, view)
- [ ] Cache de configuração recriado
- [ ] Permissões ajustadas
- [ ] Serviços reiniciados (PHP-FPM e Nginx)
- [ ] Headers CORS verificados (teste OPTIONS)
- [ ] Login no frontend testado e funcionando
- [ ] Logs verificados (sem erros CORS)
- [ ] Variáveis de ambiente validadas

## 🔧 Troubleshooting

### Problema: Headers CORS ainda não aparecem

**Solução:**
1. Verificar se o middleware está registrado em `bootstrap/app.php`
2. Limpar todos os caches novamente
3. Reiniciar PHP-FPM e Nginx
4. Verificar logs do Nginx para possíveis conflitos

### Problema: Erro 500 após deploy

**Solução:**
1. Verificar permissões dos diretórios `storage` e `bootstrap/cache`
2. Verificar logs: `tail -f storage/logs/laravel.log`
3. Verificar se todas as dependências foram instaladas: `composer install --no-dev`

### Problema: CORS funciona para OPTIONS mas não para POST

**Solução:**
1. Verificar se o Origin está na lista de origens permitidas
2. Verificar se o frontend está enviando o header `Origin` corretamente
3. Verificar logs da aplicação para possíveis erros de autenticação

### Problema: Erro "Access-Control-Allow-Credentials" conflitante

**Solução:**
1. Verificar se há múltiplos middlewares CORS configurados
2. Garantir que apenas o `CustomCorsMiddleware` está ativo
3. Verificar configuração do Nginx (não deve adicionar headers CORS)

## 📞 Suporte

Se houver problemas após o deploy:

1. Verificar logs da aplicação
2. Testar com curl para isolar o problema
3. Verificar configurações do Nginx
4. Revisar variáveis de ambiente (.env)

## 🎯 Próximos Passos

Após o deploy bem-sucedido:

1. Monitorar logs por algumas horas
2. Testar todas as funcionalidades críticas do frontend
3. Verificar se não há degradação de performance
4. Documentar qualquer ajuste adicional necessário

## 📚 Referências

- [Laravel CORS Documentation](https://laravel.com/docs/11.x/routing#cors)
- [MDN - CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Laravel Middleware](https://laravel.com/docs/11.x/middleware)

---

**Data do Deploy:** $(date)
**Versão:** 1.0.0
**Ambiente:** Produção (DigitalOcean)
**URLs:**
- Backend: https://orca-app-7hejo.ondigitalocean.app
- Frontend: https://moday-nine.vercel.app
