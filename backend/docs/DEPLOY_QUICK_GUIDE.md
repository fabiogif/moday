# Deploy Rápido - Correção CORS

## 🚀 Deploy Automático (RECOMENDADO)

```bash
# 1. Enviar script para o servidor
./send-to-server.sh

# 2. Executar deploy remotamente
ssh root@orca-app-7hejo.ondigitalocean.app 'cd /root && chmod +x deploy-on-server.sh && ./deploy-on-server.sh'
```

## ⚡ Deploy Manual Rápido

```bash
# Conectar ao servidor
ssh root@orca-app-7hejo.ondigitalocean.app

# No servidor, execute:
cd /var/www/html
git pull origin main
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
systemctl restart php8.3-fpm nginx
```

## ✅ Teste Rápido

```bash
curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/login \
  -H "Origin: https://moday-nine.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -v | grep -i "access-control"
```

Deve retornar headers com `Access-Control-Allow-Origin: https://moday-nine.vercel.app`

## 🔧 Troubleshooting Rápido

### Headers CORS não aparecem?
```bash
# Limpar todos os caches novamente
php artisan optimize:clear
php artisan config:cache

# Reiniciar serviços
systemctl restart php8.3-fpm nginx
```

### Erro 500?
```bash
# Verificar logs
tail -f /var/www/html/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Permissões?
```bash
cd /var/www/html
chown -R www-data:www-data .
chmod -R 775 storage bootstrap/cache
```

## 📋 Checklist

- [ ] Código atualizado no servidor (`git pull`)
- [ ] Caches limpos
- [ ] Permissões ajustadas
- [ ] Serviços reiniciados
- [ ] Headers CORS validados
- [ ] Login testado no frontend

## 🔗 Links

- Frontend: https://moday-nine.vercel.app
- Backend: https://orca-app-7hejo.ondigitalocean.app
- Docs completa: `DEPLOY_CORS_PRODUCTION.md`
