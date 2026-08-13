# Implementação de Rate Limiting e Proteção CSRF

Este documento descreve a implementação de rate limiting e proteção CSRF seguindo os padrões do Laravel.

## 📋 Sumário

1. [Rate Limiting](#rate-limiting)
2. [Proteção CSRF](#proteção-csrf)
3. [Como Usar](#como-usar)
4. [Testes](#testes)
5. [Configuração](#configuração)

---

## 🔒 Rate Limiting

### Limites Configurados

O sistema implementa diferentes níveis de rate limiting para proteger a API contra abuso:

#### 1. **API Padrão** (`throttle:api`)
- **Limite:** 60 requisições por minuto
- **Escopo:** Por usuário autenticado ou IP
- **Uso:** Aplicado por padrão em todas as rotas da API

#### 2. **Login** (`throttle:login`)
- **Limite:** 5 tentativas por minuto
- **Escopo:** Por IP
- **Rotas:**
  - `POST /api/auth/login`
- **Mensagem de erro:** "Muitas tentativas de login. Tente novamente em alguns minutos."

#### 3. **Registro** (`throttle:register`)
- **Limite:** 3 registros por hora
- **Escopo:** Por IP
- **Rotas:**
  - `POST /api/auth/register`
  - `POST /api/tenant`
- **Mensagem de erro:** "Limite de registros atingido. Tente novamente mais tarde."

#### 4. **Redefinição de Senha** (`throttle:password-reset`)
- **Limite:** 3 tentativas por hora
- **Escopo:** Por IP
- **Rotas:**
  - `POST /api/auth/forgot-password`
  - `POST /api/auth/reset-password`
- **Mensagem de erro:** "Muitas tentativas de redefinição de senha. Tente novamente mais tarde."

#### 5. **Operações Críticas** (`throttle:critical`)
- **Limite:** 30 requisições por minuto
- **Escopo:** Por usuário autenticado ou IP
- **Rotas:** Todas as operações POST, PUT, DELETE
  - Produtos (criar, atualizar, deletar)
  - Pedidos (criar, atualizar, deletar)
  - Mesas (criar, atualizar, deletar)
  - Categorias (criar, atualizar, deletar)
  - Usuários (criar, atualizar, deletar)
  - Clientes (criar, atualizar, deletar)
  - Perfis e Permissões (criar, atualizar, deletar)
  - Métodos de Pagamento (criar, atualizar, deletar)
- **Mensagem de erro:** "Muitas requisições. Por favor, aguarde um momento."

#### 6. **Leituras** (`throttle:read`)
- **Limite:** 100 requisições por minuto
- **Escopo:** Por usuário autenticado ou IP
- **Rotas:** Todas as operações GET
  - Listagens de produtos, pedidos, mesas, etc.
  - Visualizações individuais
  - Estatísticas e dashboard
  - Consultas de permissões e perfis

#### 7. **Hash/Criptografia** (`throttle:hash`)
- **Limite:** 10 requisições por minuto
- **Escopo:** Por IP
- **Uso:** Para operações que envolvem hash ou criptografia intensiva

### Arquivo de Configuração

No Laravel 11, o rate limiting está configurado em `bootstrap/app.php` no callback `then` do `withRouting`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Configurar rate limiting
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('login', function (Request $request) {
                return Limit::perMinute(5)->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Muitas tentativas de login. Tente novamente em alguns minutos.'
                        ], 429);
                    });
            });

            // ... outros limiters
        },
    )
    // ...
```

**Importante:** No Laravel 11, a configuração de rate limiting foi movida de `RouteServiceProvider` para `bootstrap/app.php`.

### Respostas de Rate Limit

Quando o limite é excedido, a API retorna:

```json
{
  "message": "Muitas requisições. Por favor, aguarde um momento."
}
```

**Status HTTP:** 429 Too Many Requests

**Headers incluídos:**
- `X-RateLimit-Limit`: Número máximo de requisições permitidas
- `X-RateLimit-Remaining`: Número de requisições restantes
- `Retry-After`: Segundos até poder fazer nova requisição

---

## 🛡️ Proteção CSRF

### Visão Geral

A proteção CSRF foi implementada seguindo o padrão do Laravel, adaptada para APIs REST com autenticação JWT.

### Componentes

#### 1. Middleware CSRF API (`VerifyCsrfTokenApi`)

Localizado em `app/Http/Middleware/VerifyCsrfTokenApi.php`

**Características:**
- Verifica token CSRF apenas em requisições POST, PUT, PATCH, DELETE
- Aceita token via header `X-CSRF-TOKEN` ou no corpo da requisição
- Rotas excluídas da verificação:
  - `api/auth/login`
  - `api/auth/register`
  - `api/auth/forgot-password`
  - `api/auth/refresh`
  - `api/health`
  - `api/csrf-token`
  - `api/csrf-token/verify`

**Resposta de erro:**
```json
{
  "message": "Token CSRF inválido ou ausente.",
  "error": "csrf_token_mismatch"
}
```

**Status HTTP:** 419 Session Expired

#### 2. Controller de Token CSRF

Localizado em `app/Http/Controllers/Api/CsrfTokenController.php`

**Endpoints:**

##### GET `/api/csrf-token`
Obtém um novo token CSRF

**Resposta:**
```json
{
  "csrf_token": "XYZ123...",
  "expires_at": "2025-01-23T18:30:00.000000Z"
}
```

##### POST `/api/csrf-token/verify`
Verifica se um token CSRF é válido

**Request:**
```json
{
  "token": "XYZ123..."
}
```

**Resposta (Sucesso):**
```json
{
  "valid": true,
  "message": "Token CSRF válido"
}
```

**Resposta (Erro):**
```json
{
  "valid": false,
  "message": "Token CSRF inválido"
}
```

### Registro do Middleware

O middleware foi registrado em `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... outros middlewares
    'csrf.api' => \App\Http\Middleware\VerifyCsrfTokenApi::class,
];
```

---

## 📖 Como Usar

### Rate Limiting

#### Aplicar em Rotas Individuais

```php
Route::post('/produto', [ProductController::class, 'store'])
    ->middleware('throttle:critical');
```

#### Aplicar em Grupos de Rotas

```php
Route::middleware(['throttle:read'])->group(function () {
    Route::get('/produtos', [ProductController::class, 'index']);
    Route::get('/produtos/{id}', [ProductController::class, 'show']);
});
```

#### Múltiplos Middlewares

```php
Route::post('/usuarios', [UserController::class, 'store'])
    ->middleware(['auth:api', 'throttle:critical', 'acl.permission:users.create']);
```

### Proteção CSRF

#### No Frontend (JavaScript/TypeScript)

```typescript
// 1. Obter token CSRF
const response = await fetch('/api/csrf-token');
const { csrf_token } = await response.json();

// 2. Incluir em requisições POST/PUT/DELETE
await fetch('/api/product', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrf_token,
    'Authorization': `Bearer ${jwt_token}`
  },
  body: JSON.stringify(productData)
});
```

#### Armazenar Token

```typescript
// Opção 1: sessionStorage (recomendado)
sessionStorage.setItem('csrf_token', csrf_token);

// Opção 2: Variável de estado (React/Vue)
const [csrfToken, setCsrfToken] = useState('');

// Obter na inicialização
useEffect(() => {
  fetch('/api/csrf-token')
    .then(res => res.json())
    .then(data => {
      setCsrfToken(data.csrf_token);
      sessionStorage.setItem('csrf_token', data.csrf_token);
    });
}, []);
```

#### Axios Interceptor

```typescript
import axios from 'axios';

// Configurar interceptor para adicionar token CSRF automaticamente
axios.interceptors.request.use(
  async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase() || '')) {
      let csrfToken = sessionStorage.getItem('csrf_token');
      
      if (!csrfToken) {
        const response = await axios.get('/api/csrf-token');
        csrfToken = response.data.csrf_token;
        sessionStorage.setItem('csrf_token', csrfToken);
      }
      
      config.headers['X-CSRF-TOKEN'] = csrfToken;
    }
    return config;
  },
  (error) => Promise.reject(error)
);
```

#### Aplicar Middleware em Rotas

Para proteger rotas específicas com CSRF:

```php
Route::post('/sensitive-operation', [Controller::class, 'method'])
    ->middleware(['auth:api', 'csrf.api']);
```

---

## 🧪 Testes

### Testar Rate Limiting

#### 1. Teste de Login (5 tentativas por minuto)

```bash
# Execute 6 vezes rapidamente
for i in {1..6}; do
  curl -X POST http://localhost/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"wrong"}' \
    -w "\nStatus: %{http_code}\n"
done
```

**Resultado esperado:** As primeiras 5 requisições retornam 401 (não autorizado), a 6ª retorna 429 (rate limit).

#### 2. Teste de Operações Críticas (30 por minuto)

```bash
# Script para testar criação de produtos
for i in {1..35}; do
  curl -X POST http://localhost/api/product \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer YOUR_TOKEN" \
    -d "{\"name\":\"Product $i\"}" \
    -w "\nRequest $i - Status: %{http_code}\n"
done
```

**Resultado esperado:** As primeiras 30 requisições têm sucesso, da 31ª em diante retornam 429.

#### 3. Teste de Leitura (100 por minuto)

```bash
for i in {1..105}; do
  curl -X GET http://localhost/api/product \
    -H "Authorization: Bearer YOUR_TOKEN" \
    -w "\nRequest $i - Status: %{http_code}\n"
done
```

**Resultado esperado:** As primeiras 100 requisições têm sucesso, da 101ª em diante retornam 429.

### Testar Proteção CSRF

#### 1. Obter Token CSRF

```bash
curl -X GET http://localhost/api/csrf-token \
  -H "Content-Type: application/json"
```

**Resultado esperado:**
```json
{
  "csrf_token": "XYZ123...",
  "expires_at": "2025-01-23T18:30:00.000000Z"
}
```

#### 2. Testar Requisição sem Token CSRF

```bash
curl -X POST http://localhost/api/product \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"Produto Teste"}' \
  -w "\nStatus: %{http_code}\n"
```

**Resultado esperado (se middleware csrf.api aplicado):**
```json
{
  "message": "Token CSRF inválido ou ausente.",
  "error": "csrf_token_mismatch"
}
```
**Status:** 419

#### 3. Testar Requisição com Token CSRF Válido

```bash
# Primeiro obter o token
CSRF_TOKEN=$(curl -s http://localhost/api/csrf-token | jq -r '.csrf_token')

# Depois usar em requisição
curl -X POST http://localhost/api/product \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -d '{"name":"Produto Teste"}' \
  -w "\nStatus: %{http_code}\n"
```

**Resultado esperado:** Status 200 ou 201 (sucesso).

#### 4. Testar Verificação de Token

```bash
curl -X POST http://localhost/api/csrf-token/verify \
  -H "Content-Type: application/json" \
  -d "{\"token\":\"$CSRF_TOKEN\"}"
```

**Resultado esperado:**
```json
{
  "valid": true,
  "message": "Token CSRF válido"
}
```

---

## ⚙️ Configuração

### Variáveis de Ambiente

Adicione ao arquivo `.env`:

```env
# Session (necessário para CSRF)
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Rate Limiting (opcional, valores padrão)
THROTTLE_API_PER_MINUTE=60
THROTTLE_LOGIN_PER_MINUTE=5
THROTTLE_REGISTER_PER_HOUR=3
THROTTLE_CRITICAL_PER_MINUTE=30
THROTTLE_READ_PER_MINUTE=100
```

### Configuração de CORS

Certifique-se de que o `config/cors.php` inclui o header CSRF:

```php
'allowed_headers' => [
    'Accept',
    'Authorization',
    'Content-Type',
    'X-Requested-With',
    'X-CSRF-TOKEN'  // Importante!
],

'supports_credentials' => true,  // Necessário para sessões
```

### Configuração de Sessão

O arquivo `config/session.php` deve estar configurado:

```php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => false,
'encrypt' => false,
'http_only' => true,
'same_site' => 'lax',
```

### Habilitar CSRF em Rotas Específicas

Por padrão, o CSRF não está habilitado globalmente. Para ativar em rotas específicas:

```php
// Em routes/api.php
Route::middleware(['auth:api', 'csrf.api'])->group(function () {
    Route::post('/sensitive-data', [Controller::class, 'store']);
    Route::put('/sensitive-data/{id}', [Controller::class, 'update']);
});
```

### Personalizar Mensagens de Erro

No `bootstrap/app.php`, você pode personalizar as mensagens no callback `then` do `withRouting`:

```php
->withRouting(
    // ...
    then: function () {
        RateLimiter::for('custom', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Sua mensagem personalizada aqui',
                        'retry_after' => 60
                    ], 429);
                });
        });
    },
)
```

---

## 🔍 Troubleshooting

### Headers de Rate Limit não aparecem

Certifique-se de que o middleware `throttle` está aplicado:

```php
Route::get('/api/test', [Controller::class, 'test'])
    ->middleware('throttle:api');
```

### Token CSRF sempre inválido

1. Verifique se a sessão está configurada corretamente
2. Certifique-se de que `supports_credentials` está `true` no CORS
3. Verifique se o cookie de sessão está sendo enviado nas requisições

### Rate limit muito restritivo

Ajuste os valores em `bootstrap/app.php` dentro do callback `then`:

```php
->withRouting(
    // ...
    then: function () {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    },
)

---

## 📚 Referências

- [Laravel Rate Limiting](https://laravel.com/docs/11.x/routing#rate-limiting)
- [Laravel CSRF Protection](https://laravel.com/docs/11.x/csrf)
- [HTTP 429 Too Many Requests](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429)
- [HTTP 419 Session Expired](https://httpstatuses.com/419)

---

## ✅ Checklist de Implementação

- [x] Rate limiting configurado no RouteServiceProvider
- [x] Diferentes níveis de rate limiting (login, register, critical, read)
- [x] Middleware CSRF API criado
- [x] Controller de token CSRF implementado
- [x] Endpoints de CSRF adicionados às rotas
- [x] Rate limiting aplicado em todas as rotas da API
- [x] Middleware CSRF registrado no Kernel
- [x] Documentação completa criada
- [x] Exemplos de uso fornecidos
- [x] Testes documentados

---

**Data de Implementação:** Janeiro 2025  
**Versão do Laravel:** 11.x  
**Desenvolvedor:** Sistema Moday
