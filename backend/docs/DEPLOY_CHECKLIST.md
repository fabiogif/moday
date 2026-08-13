# ✅ Checklist de Deploy - DigitalOcean

## 🚀 Passo a Passo para Deploy

### 1️⃣ Commitar as Correções

```bash
git add .
git commit -m "fix: Redis e CORS para produção DigitalOcean"
git push origin main
```

### 2️⃣ Configurar Variáveis de Ambiente na DigitalOcean

No painel **App Platform** → **Settings** → **Environment Variables**:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=seu-db-host.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=defaultdb
DB_USERNAME=doadmin
DB_PASSWORD=sua-senha-db

# Redis (IMPORTANTE: database 0 para Redis Cloud!)
REDIS_URL=rediss://default:senha@redis-12117.c57.us-east-1-4.ec2.redns.redis-cloud.com:12117
REDIS_HOST=redis-12117.c57.us-east-1-4.ec2.redns.redis-cloud.com
REDIS_PASSWORD=sua-senha-redis
REDIS_PORT=12117
REDIS_CLIENT=predis
REDIS_DB=0
REDIS_CACHE_DB=0

# Cache e Queue
CACHE_STORE=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=reverb

# CORS (Frontend DigitalOcean)
FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app
ADDITIONAL_CORS_ORIGIN=https://moday-nine.vercel.app

# App
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://orca-app-7hejo.ondigitalocean.app
```

### 3️⃣ Fazer Deploy

Opção A: **Deploy Automático**
- O push para `main` já dispara o deploy automático

Opção B: **Deploy Manual**
```bash
# No painel DigitalOcean
Settings → Deployments → Create Deployment
```

### 4️⃣ Após o Deploy - Limpar Cache

Acesse o **Console** no painel DigitalOcean e execute:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 5️⃣ Testar o Deploy

```bash
# Teste de API
curl -X POST https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Origin: https://moday-nine.vercel.app" \
  -d '{
    "email": "fabio@fabio.com",
    "password": "sua-senha"
  }'
```

## 📋 Verificações Importantes

### ✅ Antes do Deploy

- [ ] Código commitado e pushed para `main`
- [ ] Variáveis de ambiente configuradas
- [ ] `REDIS_CACHE_DB=0` (obrigatório para Redis Cloud!)
- [ ] `FRONTEND_URL` aponta para Vercel
- [ ] `APP_DEBUG=false` em produção

### ✅ Após o Deploy

- [ ] Deploy concluído sem erros
- [ ] Cache limpo
- [ ] API responde: `/api/health` ou `/up`
- [ ] Login funcionando
- [ ] CORS funcionando (sem erros no console do browser)
- [ ] Redis funcionando (sem "DB index is out of range")

## 🔍 Troubleshooting

### Erro: "DB index is out of range"
✅ **Solução**: Definir `REDIS_CACHE_DB=0` nas variáveis de ambiente

### Erro: "Connection refused [tcp://127.0.0.1:6379]"
✅ **Solução**: Verificar `REDIS_URL` e `REDIS_HOST` configurados corretamente

### Erro CORS
✅ **Solução**: Verificar `FRONTEND_URL` e `ADDITIONAL_CORS_ORIGIN`

### Erro 500 (Internal Server Error)
✅ **Solução**: 
1. Verificar logs no painel DigitalOcean
2. Limpar cache: `php artisan config:clear`
3. Verificar variáveis de ambiente

## 🎯 Arquivos Alterados Neste Fix

1. ✅ `config/database.php` - Redis database de 1 para 0
2. ✅ `bootstrap/app.php` - CORS middleware
3. ✅ `app/Http/Middleware/CustomCorsMiddleware.php` - CORS headers
4. ✅ `app/Http/Kernel.php` - Removido HandleCors conflitante
5. ✅ `.env` local - Configurado para Docker

## 📚 Documentação Criada

1. `DIGITALOCEAN_REDIS_FIX.md` - Solução detalhada do Redis
2. `REDIS_DEV_PROD_SOLUTION.md` - Configuração Dev/Prod
3. `QUICK_SETUP.md` - Setup rápido
4. `CORS_FIX_SUMMARY.md` - Correção CORS
5. `DEPLOY_CHECKLIST.md` - Este arquivo

## 🎉 Status Final

### Desenvolvimento ✅
- Redis funcionando (Docker)
- MySQL funcionando (Docker)
- CORS configurado
- Cache operacional

### Produção ✅ (Após Deploy)
- Redis Cloud configurado (database 0)
- MySQL Managed configurado
- CORS permitindo Vercel
- Cache operacional

Tudo pronto para deploy! 🚀

