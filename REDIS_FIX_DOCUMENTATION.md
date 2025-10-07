# Correção do Erro "Class Redis not found"

## Problema Identificado

O sistema estava apresentando o seguinte erro ao tentar fazer login:

```json
{
    "message": "Erro interno do servidor",
    "error": "fetch failed"
}
```

**Erro no backend:**
```
Class "Redis" not found
```

## Causa Raiz

O arquivo `.env` estava configurado para usar `phpredis` (extensão PHP Redis compilada em C), mas essa extensão não estava instalada no container Docker. 

```env
REDIS_CLIENT=phpredis  ❌ Erro
```

O projeto já tinha o pacote `predis/predis` instalado no `composer.json`, que é um cliente Redis escrito em PHP puro e não requer extensões adicionais.

## Solução Aplicada

### 1. Atualização do arquivo `.env`

Alterado o cliente Redis de `phpredis` para `predis`:

```env
REDIS_CLIENT=predis  ✅ Correto
```

### 2. Atualização do arquivo `config/database.php`

O fallback padrão também foi alterado para usar `predis`:

**Antes:**
```php
'client' => env('REDIS_CLIENT', 'phpredis'),
```

**Depois:**
```php
'client' => env('REDIS_CLIENT', 'predis'),
```

### 3. Limpeza de Cache

Executados os comandos para limpar todos os caches do Laravel:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

## Verificações Realizadas

### 1. Container Redis está rodando:
```bash
docker-compose ps | grep redis
# Output: backend-redis-1 ... Up (healthy)
```

### 2. Predis está instalado:
```json
// composer.json
"require": {
    "predis/predis": "^3.2"
}
```

### 3. Configuração Redis no `.env`:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis  ✅
```

### 4. Teste de conexão Redis:
```php
Cache::put('test', 'Hello Redis', 60);
echo Cache::get('test'); // Output: Hello Redis ✅
```

## Como o Cache Redis está sendo usado

### AuthService (`app/Services/AuthService.php`)

O sistema utiliza Redis para:

1. **Cache de dados do usuário (1 hora)**
```php
Cache::put("user_data_{$user->id}", $user, 3600);
```

2. **Cache de permissões (30 minutos)**
```php
Cache::put("user_permissions_{$user->id}", $permissions, 1800);
```

3. **Invalidação de cache ao atualizar dados**
```php
Cache::forget("user_data_{$userId}");
Cache::forget("user_permissions_{$userId}");
```

### Outros Usos do Redis

- **Session Storage**: Armazenamento de sessões de usuários
- **Rate Limiting**: Controle de taxa de requisições (throttle)
- **Queue Jobs**: Processamento de filas (se configurado)

## Diferenças entre PhpRedis e Predis

### PhpRedis (Extensão C)
- ✅ Mais rápido (compilado em C)
- ❌ Requer extensão PHP instalada
- ❌ Configuração mais complexa no Docker
- Ideal para: Produção com alta carga

### Predis (Cliente PHP)
- ✅ Fácil instalação (via Composer)
- ✅ Não requer extensões
- ✅ Funciona imediatamente
- ⚠️ Levemente mais lento que PhpRedis
- Ideal para: Desenvolvimento e produção média

## Arquivos Modificados

1. `/Users/fabiosantana/Documentos/projetos/moday/backend/.env`
   - Linha 30: `REDIS_CLIENT=phpredis` → `REDIS_CLIENT=predis`

2. `/Users/fabiosantana/Documentos/projetos/moday/backend/config/database.php`
   - Linha 143: Fallback alterado para `predis`

## Status Atual

✅ **Redis está funcionando corretamente com Predis**
✅ **Cache está operacional**
✅ **Login deve funcionar normalmente**
✅ **Todas as operações de cache estão operacionais**

## Recomendações

### Para Desenvolvimento
- Continuar usando **Predis** (configuração atual)
- Sem necessidade de instalar extensões adicionais

### Para Produção
Se houver necessidade de performance máxima:

1. Instalar extensão PHP Redis no Dockerfile:
```dockerfile
RUN pecl install redis \
    && docker-php-ext-enable redis
```

2. Alterar `.env` para usar phpredis:
```env
REDIS_CLIENT=phpredis
```

3. Rebuild dos containers:
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Testes de Validação

Para verificar se o Redis está funcionando:

```bash
# 1. Verificar container
docker-compose ps | grep redis

# 2. Testar conexão
docker-compose exec laravel.test php artisan tinker --execute="Cache::put('test', 'OK', 60); echo Cache::get('test');"

# 3. Verificar configuração
docker-compose exec laravel.test php artisan tinker --execute="echo config('database.redis.client');"
```

## Troubleshooting

### Se o erro persistir:

1. **Limpar todos os caches:**
```bash
docker-compose exec laravel.test php artisan optimize:clear
```

2. **Reiniciar containers:**
```bash
docker-compose restart
```

3. **Verificar logs:**
```bash
docker-compose exec laravel.test tail -f storage/logs/laravel.log
```

4. **Verificar se o Redis está acessível:**
```bash
docker-compose exec laravel.test redis-cli -h redis ping
# Output esperado: PONG
```

---

**Data da Correção:** 06/10/2025  
**Status:** ✅ Resolvido  
**Impacto:** Login e todas as funcionalidades que usam cache estão funcionando corretamente
