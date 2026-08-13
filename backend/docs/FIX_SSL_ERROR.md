# FIX: SSL Error - Configuração de Ambiente Digital Ocean

## 🔴 ERRO ATUAL
```
stream_socket_enable_crypto(): SSL operation failed with code 1
error:0A00010B:SSL routines::wrong version number
```

## 🎯 CAUSA
O Laravel está tentando conectar ao **REDIS em 127.0.0.1:6379** (localhost), mas no Digital Ocean App Platform não existe servidor Redis local. Isso causa erro SSL quando tenta a conexão.

## ✅ SOLUÇÃO IMEDIATA

### Opção 1: Usar FILE cache (Recomendado para começar)

No painel Digital Ocean:

1. Acesse: https://cloud.digitalocean.com/apps
2. Clique no app (orca-app-7hejo)
3. Vá em **Settings** → **App-Level Environment Variables**
4. **EDITE** ou **ADICIONE** estas variáveis:

```bash
# Cache e Session - Usar FILE em vez de REDIS
CACHE_DRIVER=file
SESSION_DRIVER=file

# Redis - Remover ou deixar vazio
REDIS_HOST=
REDIS_PORT=
REDIS_PASSWORD=

# App Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://orca-app-7hejo.ondigitalocean.app
FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app
```

5. **Save** (o app vai reiniciar automaticamente)

### Opção 2: Usar Digital Ocean Managed Redis (Mais robusto)

Se você tem um cluster Redis no Digital Ocean:

1. Crie um Managed Redis Database:
   - Apps → Databases → Create Database
   - Engine: Redis
   - Plan: Dev (grátis) ou Basic

2. Pegue as credenciais de conexão

3. Configure no App:
```bash
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=your-redis-host.ondigitalocean.com
REDIS_PORT=25061
REDIS_PASSWORD=your-redis-password
REDIS_CLIENT=phpredis
REDIS_TLS=true
```

## 📋 VARIÁVEIS OBRIGATÓRIAS PARA PRODUÇÃO

Configure TODAS estas no Digital Ocean App Environment Variables:

```bash
# === ESSENCIAIS ===
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:PQlyU00BWelmblrFjKliAUGTWtep70IJYcBkH03fXPQ=
APP_URL=https://orca-app-7hejo.ondigitalocean.app
FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app

# === CACHE & SESSION ===
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# === DATABASE ===
# Pegue estes valores do seu Managed Database no Digital Ocean
DB_CONNECTION=mysql
DB_HOST=your-db-host.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=defaultdb
DB_USERNAME=doadmin
DB_PASSWORD=your-db-password

# === QUEUE ===
QUEUE_CONNECTION=database

# === LOGGING ===
LOG_CHANNEL=stack
LOG_LEVEL=error

# === JWT ===
JWT_SECRET=0WkxqDHwoc6cuIGgzqoUsbIU8trXgQYuvE7G63Adn0qTLdGlUNHkruQW49kQNbvl

# === MAIL (opcional) ===
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# === BROADCAST (desabilitar) ===
BROADCAST_DRIVER=log
```

## 🚀 PASSO A PASSO

### 1. Configurar Variáveis de Ambiente

```bash
# No painel Digital Ocean:
Apps → orca-app-7hejo → Settings → Environment Variables → Edit

# MUDAR de REDIS para FILE:
CACHE_DRIVER = file
SESSION_DRIVER = file

# LIMPAR Redis config:
REDIS_HOST = (deixe vazio ou delete)
REDIS_PORT = (deixe vazio ou delete)

# ADICIONAR:
APP_ENV = production
APP_DEBUG = false
APP_URL = https://orca-app-7hejo.ondigitalocean.app
FRONTEND_URL = https://clownfish-app-rr5rv.ondigitalocean.app

# Save
```

### 2. Aguardar Restart (1-2 minutos)

O app vai reiniciar automaticamente quando você salvar.

### 3. Limpar Cache

No console do app:
```bash
cd ~
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 4. Testar

Tente fazer login no frontend novamente.

## 🔍 VERIFICAR SE FUNCIONOU

No console do app:
```bash
# Ver configuração atual
php artisan config:show cache

# Deve mostrar:
# default: "file"

# Testar cache
php artisan tinker
>>> cache()->put('test', 'works');
>>> cache()->get('test');
# Deve retornar: "works"
```

## ⚠️ IMPORTANTE

**NÃO use Redis** se você não tem um servidor Redis configurado!

A configuração `.env` local (com REDIS_HOST=127.0.0.1) funciona localmente porque você tem Redis rodando no Docker, mas NÃO funciona no Digital Ocean App Platform.

## 📝 CHECKLIST

- [ ] Mudei CACHE_DRIVER de redis para file
- [ ] Mudei SESSION_DRIVER de redis para file
- [ ] Removi ou limpei REDIS_HOST
- [ ] Configurei APP_URL corretamente
- [ ] Configurei FRONTEND_URL
- [ ] Mudei APP_ENV para production
- [ ] Mudei APP_DEBUG para false
- [ ] Salvei as mudanças
- [ ] App reiniciou
- [ ] Limpei o cache
- [ ] Testei login

## 🆘 SE AINDA DER ERRO

Verifique os logs:
```bash
# No painel Digital Ocean
Apps → orca-app-7hejo → Runtime Logs

# Procure por:
- Erros de conexão
- Erros SSL
- Erros de Redis
```

Me envie os logs se ainda houver problemas!
