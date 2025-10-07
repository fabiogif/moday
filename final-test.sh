#!/bin/bash

echo "=========================================="
echo "TESTE FINAL - DASHBOARD CARDS"
echo "=========================================="
echo ""

# Check if backend is running
echo "1. Verificando backend..."
BACKEND_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/health 2>/dev/null || echo "000")
if [ "$BACKEND_STATUS" = "000" ]; then
    echo "   ❌ Backend não está rodando em http://localhost:8000"
    echo "   Execute: cd backend && php artisan serve --port=8000"
    exit 1
else
    echo "   ✅ Backend está rodando"
fi

# Check if Redis is accessible
echo ""
echo "2. Verificando Redis..."
REDIS_TEST=$(cd backend && php artisan tinker --execute="try { Cache::put('test', 'ok', 10); echo Cache::get('test'); } catch (Exception \$e) { echo 'ERROR'; }" 2>/dev/null)
if [ "$REDIS_TEST" = "ok" ]; then
    echo "   ✅ Redis está funcionando"
else
    echo "   ❌ Redis não está acessível"
    echo "   Verifique: docker-compose ps | grep redis"
    exit 1
fi

# Test login
echo ""
echo "3. Testando autenticação..."
LOGIN_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"fabio@fabio.com","password":"123456"}')

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token // empty' 2>/dev/null)
if [ -z "$TOKEN" ]; then
    echo "   ❌ Falha no login"
    echo "   Resposta: $LOGIN_RESPONSE"
    exit 1
else
    echo "   ✅ Login bem-sucedido"
fi

# Test metrics endpoint
echo ""
echo "4. Testando endpoint de métricas..."
METRICS_RESPONSE=$(curl -s -X GET "http://localhost:8000/api/dashboard/metrics" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

METRICS_SUCCESS=$(echo "$METRICS_RESPONSE" | jq -r '.success // false' 2>/dev/null)
if [ "$METRICS_SUCCESS" = "true" ]; then
    echo "   ✅ Endpoint de métricas funcionando"
    echo ""
    echo "   Dados recebidos:"
    echo "$METRICS_RESPONSE" | jq '.data | {
      receita: .total_revenue.formatted,
      clientes: .active_clients.value,
      pedidos: .total_orders.value,
      conversao: .conversion_rate.formatted
    }' 2>/dev/null
else
    echo "   ❌ Erro no endpoint de métricas"
    echo "   Resposta: $METRICS_RESPONSE"
    exit 1
fi

echo ""
echo "=========================================="
echo "✅ TODOS OS TESTES PASSARAM!"
echo "=========================================="
echo ""
echo "Os cards do dashboard devem estar funcionando corretamente."
echo ""
echo "Para testar no navegador:"
echo "1. cd frontend && npm run dev"
echo "2. Acesse http://localhost:3000/dashboard"
echo "3. Login: fabio@fabio.com / 123456"
echo ""
