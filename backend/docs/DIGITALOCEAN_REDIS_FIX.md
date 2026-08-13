# 🔧 Correção Redis para DigitalOcean

## ❌ Erro Encontrado
```
Failed: ERR DB index is out of range [tcp://redis-12117.c57.us-east-1-4.ec2.redns.redis-cloud.com:12117]
```

## 🎯 Causa
O **Redis Cloud da DigitalOcean** permite apenas o **database index 0**. 

A configuração estava tentando usar:
- `database 0` para conexão default ✅
- `database 1` para cache ❌ (não existe no Redis Cloud)

## ✅ Solução Aplicada

### 1. Arquivo Corrigido: `config/database.php`

```php
'cache' => [
    'url' => env('REDIS_URL'),
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'username' => env('REDIS_USERNAME'),
    'password' => env('REDIS_PASSWORD'),
    'port' => env('REDIS_PORT', '6379'),
    'database' => env('REDIS_CACHE_DB', '0'), // ✅ Alterado de '1' para '0'
    'read_timeout' => 2,
    'timeout' => 2,
],
```

### 2. Variáveis de Ambiente para Produção

No painel da **DigitalOcean App Platform**, configure:

```env
# Redis Configuration (DigitalOcean Redis Cloud)
REDIS_URL=rediss://default:senha@redis-12117.c57.us-east-1-4.ec2.redns.redis-cloud.com:12117
REDIS_HOST=redis-12117.c57.us-east-1-4.ec2.redns.redis-cloud.com
REDIS_PASSWORD=sua-senha-redis
REDIS_PORT=12117
REDIS_CLIENT=predis
REDIS_DB=0
REDIS_CACHE_DB=0

# Cache, Queue, Session usando Redis
CACHE_STORE=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=reverb
```

### 3. Variáveis de Ambiente para Desenvolvimento (Docker)

No arquivo `.env` local:

```env
# Redis Configuration (Docker)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
REDIS_DB=0
REDIS_CACHE_DB=0

# Cache, Queue, Session usando Redis
CACHE_STORE=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=reverb

# Database (Docker)
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

## 🚀 Deploy na DigitalOcean

### Passo 1: Commitar as Alterações
```bash
git add config/database.php
git commit -m "fix: Redis database index para compatibilidade com Redis Cloud"
git push origin main
```

### Passo 2: Configurar Variáveis de Ambiente

No painel da **DigitalOcean**:
1. Vá em **Settings** → **Environment Variables**
2. Adicione/Atualize:
   - `REDIS_CACHE_DB=0`
   - `REDIS_DB=0`
   - Verifique se `REDIS_URL` está configurado corretamente

### Passo 3: Fazer Deploy
```bash
# O deploy automático irá pegar as mudanças do git
# Ou force o deploy manualmente no painel
```

### Passo 4: Limpar Cache em Produção
```bash
# Via painel DigitalOcean Console:
php artisan config:clear
php artisan cache:clear
```

## 📋 Checklist de Verificação

### Desenvolvimento (Local):
- [ ] `REDIS_HOST=redis` (nome do container Docker)
- [ ] `DB_HOST=mysql` (nome do container Docker)
- [ ] `REDIS_CACHE_DB=0`
- [ ] Containers rodando: `docker-compose ps`
- [ ] Redis funcionando: `docker exec backend-redis-1 redis-cli ping`

### Produção (DigitalOcean):
- [ ] `REDIS_URL` configurado com URL do Redis Cloud
- [ ] `REDIS_CACHE_DB=0`
- [ ] `REDIS_DB=0`
- [ ] Config cache limpo após deploy
- [ ] Logs sem erros de Redis

## 🧪 Teste de Verificação

### Local:
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "seu@email.com",
    "password": "sua-senha"
  }'
```

### Produção:
```bash
curl -X POST https://orca-app-7hejo.ondigitalocean.app/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Origin: https://moday-nine.vercel.app" \
  -d '{
    "email": "seu@email.com",
    "password": "sua-senha"
  }'
```

## 📝 Notas Importantes

### Redis Cloud vs Redis Local

| Recurso | Redis Cloud (DigitalOcean) | Redis Local (Docker) |
|---------|---------------------------|----------------------|
| Databases disponíveis | Apenas `0` | `0` até `15` |
| TLS/SSL | Obrigatório (rediss://) | Opcional |
| Autenticação | Obrigatória | Opcional |
| Performance | Alta latência | Baixa latência |

### Alternativas ao Redis Cloud

Se precisar de múltiplos databases:
1. **Managed Redis DigitalOcean**: Permite múltiplos databases
2. **Redis no Droplet**: Controle total
3. **Usar prefixos**: Separar dados por prefixo em vez de database

### Usar Prefixos em Vez de Databases

Já configurado em `config/database.php`:
```php
'options' => [
    'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
],
```

E em `config/cache.php`:
```php
'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
```

Isso garante que mesmo usando database `0` para tudo, as chaves não conflitam.

## ✅ Resumo da Solução

1. ✅ Alterado `REDIS_CACHE_DB` de `1` para `0` em `config/database.php`
2. ✅ Configurado variáveis de ambiente corretas para dev e prod
3. ✅ Documentado diferenças entre Redis Cloud e Redis local
4. ✅ Solução funciona em **desenvolvimento** (Docker) e **produção** (DigitalOcean)

## 🎉 Resultado

Após aplicar esta correção:
- ✅ Redis funcionando em desenvolvimento (Docker)
- ✅ Redis funcionando em produção (DigitalOcean Redis Cloud)
- ✅ Cache, Queue, Session e Broadcasting operacionais
- ✅ Sem erros de "DB index is out of range"

