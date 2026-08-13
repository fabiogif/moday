# Guia Rápido: Rate Limiting e CSRF

## 🚀 Início Rápido

### Rate Limiting

Todas as rotas da API já estão protegidas com rate limiting apropriado:

```php
// Leituras: 100 requisições/minuto
Route::get('/products', [ProductController::class, 'index'])
    ->middleware('throttle:read');

// Operações críticas: 30 requisições/minuto
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('throttle:critical');

// Login: 5 tentativas/minuto
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Registro: 3 registros/hora
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:register');
```

### CSRF Protection

#### 1. Obter Token CSRF

```javascript
// JavaScript/TypeScript
const response = await fetch('/api/csrf-token');
const { csrf_token } = await response.json();
```

#### 2. Usar em Requisições

```javascript
// Opção 1: Header
await fetch('/api/product', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': csrf_token,
    'Authorization': `Bearer ${jwt_token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(data)
});

// Opção 2: No corpo da requisição
await fetch('/api/product', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwt_token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    ...data,
    csrf_token: csrf_token
  })
});
```

## 📊 Limites Configurados

| Categoria | Limite | Rotas Exemplo |
|-----------|--------|---------------|
| API Padrão | 60/min | Todas (padrão) |
| Leituras | 100/min | GET /product, /dashboard |
| Críticas | 30/min | POST/PUT/DELETE |
| Login | 5/min | POST /auth/login |
| Registro | 3/hora | POST /auth/register |
| Reset Senha | 3/hora | POST /auth/forgot-password |

## 🛠️ Personalização

### Adicionar Novo Rate Limiter

Em `bootstrap/app.php`, dentro do callback `then` do `withRouting`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        RateLimiter::for('custom', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Sua mensagem personalizada'
                    ], 429);
                });
        });
    },
)

### Aplicar em Rota

```php
Route::post('/endpoint', [Controller::class, 'method'])
    ->middleware('throttle:custom');
```

## 🧪 Testes

Execute o script de testes:

```bash
./test-rate-limiting-csrf.sh
```

Ou teste manualmente:

```bash
# Testar rate limiting
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@test.com","password":"test"}'
done

# Testar CSRF token
curl http://localhost:8000/api/csrf-token
```

## 📝 Notas Importantes

1. **Rate Limiting** é aplicado automaticamente em todas as rotas da API
2. **CSRF Protection** está disponível mas não é aplicado por padrão em rotas API (use JWT)
3. Para habilitar CSRF em rotas específicas, adicione `middleware('csrf.api')`
4. Headers de rate limit são incluídos automaticamente nas respostas

## ⚠️ Troubleshooting

### Rate limit muito restritivo?

Ajuste em `bootstrap/app.php` dentro do callback `then`:
```php
->withRouting(
    then: function () {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    },
)

### CSRF não funciona?

1. Verifique se a sessão está configurada (necessário para CSRF)
2. Certifique-se que CORS permite o header `X-CSRF-TOKEN`
3. Verifique se `supports_credentials` está `true` no CORS

## 🔗 Referências

- Documentação completa: `RATE_LIMITING_E_CSRF.md`
- Script de testes: `test-rate-limiting-csrf.sh`
- Laravel Rate Limiting: https://laravel.com/docs/11.x/routing#rate-limiting
