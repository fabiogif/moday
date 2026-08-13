# 🚀 DEPLOY URGENTE - DIGITAL OCEAN

## O PROBLEMA
Os arquivos corrigidos estão no seu computador local, mas NÃO estão no servidor da Digital Ocean ainda!

## SOLUÇÃO RÁPIDA - 3 PASSOS

### PASSO 1: Conecte ao servidor Digital Ocean

Abra um terminal e conecte-se ao servidor:

```bash
# Use o painel da Digital Ocean para obter acesso SSH
# Ou use a console do navegador diretamente no painel da Digital Ocean
```

**Opção A - Via SSH:**
```bash
ssh root@orca-app-7hejo.ondigitalocean.app
# ou
ssh seu-usuario@orca-app-7hejo.ondigitalocean.app
```

**Opção B - Console do navegador:**
1. Acesse https://cloud.digitalocean.com/
2. Vá em Droplets
3. Clique no seu droplet (orca-app-7hejo)
4. Clique em "Console" ou "Access" > "Launch Console"

### PASSO 2: Navegue até o diretório do backend

```bash
# Descobrir onde está o projeto
cd /var/www/
ls -la

# Ou pode estar em:
cd /var/www/html
# ou
cd /home/seu-usuario/backend
# ou  
cd /root/backend

# Liste os arquivos para confirmar que está no lugar certo
ls -la
# Você deve ver: artisan, composer.json, app/, public/, etc.
```

### PASSO 3: Edite os arquivos diretamente no servidor

#### 3.1 - Editar CustomCorsMiddleware.php

```bash
nano app/Http/Middleware/CustomCorsMiddleware.php
```

**Encontre esta linha (por volta da linha 18-23):**
```php
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'https://localhost:3000',
    'https://localhost:3001',
    'https://moday-nine.vercel.app',
];
```

**Substitua por:**
```php
$allowedOrigins = array_filter([
    'http://localhost:3000',
    'http://localhost:3001',
    'https://localhost:3000',
    'https://localhost:3001',
    'https://moday-nine.vercel.app',
    'https://clownfish-app-rr5rv.ondigitalocean.app',
    env('FRONTEND_URL'),
    env('ADDITIONAL_CORS_ORIGIN'),
]);
```

**Salve:** Ctrl+O, Enter, Ctrl+X

#### 3.2 - Editar .htaccess (CRÍTICO!)

```bash
nano public/.htaccess
```

**REMOVA estas linhas se existirem (geralmente no início do arquivo):**
```apache
<IfModule mod_headers.c>
    # CORS Headers
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept, Authorization"
    Header always set Access-Control-Max-Age "3600"
</IfModule>
```

**Salve:** Ctrl+O, Enter, Ctrl+X

#### 3.3 - Atualizar .env

```bash
nano .env
```

**Adicione esta linha no final do arquivo (se não existir):**
```
FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app
```

**Salve:** Ctrl+O, Enter, Ctrl+X

### PASSO 4: Limpar cache e reiniciar

```bash
# Limpar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Otimizar
php artisan config:cache
php artisan route:cache

# Reiniciar PHP-FPM (tente um destes)
sudo systemctl restart php8.2-fpm
# ou
sudo systemctl restart php8.1-fpm
# ou
sudo systemctl restart php-fpm

# Reiniciar Nginx
sudo systemctl restart nginx
# ou se for Apache
sudo systemctl restart apache2
```

### PASSO 5: Testar

```bash
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login
```

**Você DEVE ver:**
```
Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app
Access-Control-Allow-Credentials: true
```

**NÃO deve ver:**
```
Access-Control-Allow-Origin: *
```

### PASSO 6: Teste no navegador

Agora abra o frontend e tente fazer login!

---

## ATALHO - Script Completo (Copie e Cole)

Se preferir, copie e cole este script inteiro no terminal do servidor:

```bash
#!/bin/bash
cd /var/www/backend  # AJUSTE O CAMINHO SE NECESSÁRIO

# Backup dos arquivos
cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.backup
cp public/.htaccess public/.htaccess.backup

# Adicionar FRONTEND_URL ao .env
if ! grep -q "FRONTEND_URL=" .env; then
    echo "FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app" >> .env
fi

# Limpar cache
php artisan config:clear
php artisan cache:clear  
php artisan route:clear
php artisan config:cache

# Reiniciar serviços
systemctl restart php8.2-fpm 2>/dev/null || systemctl restart php-fpm
systemctl restart nginx 2>/dev/null || systemctl restart apache2

echo "✅ Pronto! Agora teste o login no frontend."
```

---

## CHECKLIST

- [ ] Conectei ao servidor Digital Ocean
- [ ] Editei `app/Http/Middleware/CustomCorsMiddleware.php`
- [ ] Removi CORS do `public/.htaccess`  
- [ ] Adicionei `FRONTEND_URL` ao `.env`
- [ ] Limpei o cache com `php artisan config:clear`
- [ ] Reiniciei PHP-FPM e Nginx
- [ ] Testei com curl
- [ ] Testei login no navegador

---

## Se ainda não funcionar

```bash
# Ver logs em tempo real
tail -f storage/logs/laravel.log

# Ver últimas linhas
tail -100 storage/logs/laravel.log

# Ver configuração nginx
cat /etc/nginx/sites-available/default
```

Me envie a saída desses comandos para ajudar mais!
