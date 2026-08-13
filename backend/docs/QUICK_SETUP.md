# ⚡ Setup Rápido - Redis Dev/Prod

## 🎯 Solução do Problema: Connection refused [tcp://127.0.0.1:6379]

### Para DESENVOLVIMENTO (com Docker/Redis):
```bash
# Seu .env já está configurado! Redis funcionando! ✅
php artisan about | grep -A 8 "Drivers"
```

### Para PRODUÇÃO (DigitalOcean sem Redis):

#### Opção 1: Via Painel DigitalOcean
Adicionar estas variáveis de ambiente no App Platform:
```
CACHE_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
BROADCAST_DRIVER=log
```

#### Opção 2: Via .env do servidor
```bash
# SSH no servidor
ssh seu-servidor

# Editar .env
nano /var/www/html/.env

# Alterar estas linhas:
BROADCAST_DRIVER=log
CACHE_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

# Limpar cache
php artisan config:clear
php artisan cache:clear
```

## 🚀 Comando Único

Execute este comando no servidor de produção:
```bash
sed -i \
  -e 's/^BROADCAST_DRIVER=.*/BROADCAST_DRIVER=log/' \
  -e 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' \
  -e '/^CACHE_DRIVER=/a CACHE_STORE=file' \
  -e 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' \
  -e 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' \
  .env && php artisan config:clear
```

## ✅ Verificar se Funcionou

```bash
php artisan about | grep -A 8 "Drivers"
```

**Deve mostrar:**
- Cache: file ✅
- Queue: sync ✅  
- Session: file ✅
- Broadcasting: log ✅

## 📁 Arquivos Criados

1. ✅ `config/cache.php` - Atualizado com fallback
2. ✅ `config/queue.php` - Atualizado com padrão sync
3. ✅ `config/database.php` - Redis com timeout rápido
4. ✅ `REDIS_DEV_PROD_SOLUTION.md` - Documentação completa
5. ✅ `setup-redis-config.sh` - Script automático
6. ✅ `env.development.example` - Exemplo desenvolvimento
7. ✅ `env.production.noredis.example` - Exemplo produção

## 🎉 Pronto!

Sua aplicação agora funciona em **qualquer ambiente**!

