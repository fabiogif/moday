# Implementação de Rate Limiting e CSRF - Laravel 11

## ✅ Implementação Concluída

### Mudanças Realizadas

#### 1. **Rate Limiting (bootstrap/app.php)**

Configurado seguindo o padrão do Laravel 11, todos os rate limiters foram movidos para `bootstrap/app.php`:

- ✅ **API Padrão**: 60 requisições/minuto
- ✅ **Login**: 5 tentativas/minuto por IP
- ✅ **Registro**: 3 registros/hora por IP
- ✅ **Reset de Senha**: 3 tentativas/hora por IP
- ✅ **Operações Críticas**: 30 requisições/minuto (POST/PUT/DELETE)
- ✅ **Leituras**: 100 requisições/minuto (GET)

#### 2. **Proteção CSRF**

- ✅ Middleware CSRF API criado (`VerifyCsrfTokenApi`)
- ✅ Controller para tokens CSRF (`CsrfTokenController`)
- ✅ Endpoints:
  - `GET /api/csrf-token` - Obter token
  - `POST /api/csrf-token/verify` - Verificar token

#### 3. **Rotas Atualizadas**

Todas as rotas da API foram atualizadas com rate limiting apropriado:

```php
// Autenticação
POST /api/auth/login          -> throttle:login (5/min)
POST /api/auth/register        -> throttle:register (3/hour)
POST /api/auth/forgot-password -> throttle:password-reset (3/hour)

// Leituras
GET /api/product              -> throttle:read (100/min)
GET /api/dashboard            -> throttle:read (100/min)

// Operações Críticas
POST /api/product             -> throttle:critical (30/min)
PUT /api/product/{id}         -> throttle:critical (30/min)
DELETE /api/product/{id}      -> throttle:critical (30/min)
```

#### 4. **Middleware Registrado**

No `bootstrap/app.php`:
```php
$middleware->alias([
    'acl.permission' => \App\Http\Middleware\PermissionMiddleware::class,
    'csrf.api' => \App\Http\Middleware\VerifyCsrfTokenApi::class,
]);
```

## 📁 Arquivos Criados/Modificados

### Criados:
1. `app/Http/Controllers/Api/CsrfTokenController.php`
2. `app/Http/Middleware/VerifyCsrfTokenApi.php`
3. `RATE_LIMITING_E_CSRF.md` (Documentação completa)
4. `GUIA_RAPIDO_RATE_LIMITING_CSRF.md` (Guia rápido)
5. `test-rate-limiting-csrf.sh` (Script de testes)
6. `IMPLEMENTACAO_RATE_LIMITING_CSRF_LARAVEL11.md` (Este arquivo)

### Modificados:
1. `bootstrap/app.php` - Adicionado rate limiting e registro de middleware
2. `app/Providers/RouteServiceProvider.php` - Removida configuração antiga de rate limiting
3. `routes/api.php` - Aplicado rate limiting em todas as rotas
4. `app/Http/Kernel.php` - Removido (Laravel 11 não usa mais)

## 🧪 Verificação

Execute os seguintes comandos para verificar:

```bash
# Verificar rotas com rate limiting
php artisan route:list --json | jq '.[] | select(.uri == "api/auth/login") | {uri, middleware}'

# Resultado esperado:
# {
#   "uri": "api/auth/login",
#   "middleware": [
#     "api",
#     "Illuminate\\Routing\\Middleware\\ThrottleRequests:login"
#   ]
# }

# Verificar endpoints CSRF
php artisan route:list --path=csrf

# Resultado esperado:
# GET|HEAD   api/csrf-token
# POST       api/csrf-token/verify
```

## 🚀 Como Usar

### Frontend - Obter Token CSRF

```javascript
// 1. Obter token
const { csrf_token } = await fetch('/api/csrf-token').then(r => r.json());

// 2. Usar em requisições
await fetch('/api/product', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': csrf_token,
    'Authorization': `Bearer ${jwt_token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(data)
});
```

### Testar Rate Limiting

```bash
# Executar script de testes
./test-rate-limiting-csrf.sh

# Ou testar manualmente login (deve bloquear na 6ª tentativa)
for i in {1..6}; do
  curl -X POST http://localhost:8000/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@test.com","password":"wrong"}'
done
```

## 📊 Limites Configurados

| Categoria | Limite | Mensagem de Erro |
|-----------|--------|------------------|
| Login | 5/minuto | "Muitas tentativas de login. Tente novamente em alguns minutos." |
| Registro | 3/hora | "Limite de registros atingido. Tente novamente mais tarde." |
| Reset Senha | 3/hora | "Muitas tentativas de redefinição de senha. Tente novamente mais tarde." |
| Críticas | 30/minuto | "Muitas requisições. Por favor, aguarde um momento." |
| Leituras | 100/minuto | (mensagem padrão) |
| API Padrão | 60/minuto | (mensagem padrão) |

## 🔧 Personalização

Para ajustar os limites, edite `bootstrap/app.php`:

```php
->withRouting(
    // ...
    then: function () {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip()); // Alterado de 5 para 10
        });
    },
)
```

## ✅ Checklist de Implementação

- [x] Rate limiting configurado no Laravel 11 (bootstrap/app.php)
- [x] Removida configuração antiga do RouteServiceProvider
- [x] 6 tipos de rate limiters implementados
- [x] Middleware CSRF API criado
- [x] Controller de token CSRF implementado
- [x] Endpoints CSRF adicionados
- [x] Rate limiting aplicado em todas as rotas
- [x] Middleware CSRF registrado
- [x] Documentação completa criada
- [x] Script de testes criado
- [x] Verificação realizada

## 📚 Documentação

- **Completa**: `RATE_LIMITING_E_CSRF.md`
- **Guia Rápido**: `GUIA_RAPIDO_RATE_LIMITING_CSRF.md`
- **Laravel Docs**: https://laravel.com/docs/11.x/routing#rate-limiting

## ⚠️ Notas Importantes

1. **Laravel 11** mudou a estrutura - rate limiting agora é configurado em `bootstrap/app.php`
2. Rate limiting está **ativo** em todas as rotas da API
3. CSRF está **disponível** mas não aplicado por padrão (use JWT para APIs)
4. Para habilitar CSRF em rotas específicas, adicione `->middleware('csrf.api')`

---

**Implementado em:** Janeiro 2025  
**Laravel:** 11.46.0  
**PHP:** 8.4.12
