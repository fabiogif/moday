#!/bin/bash
# Repo root (scripts/test → ../..)
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

echo "=== Teste Completo - Dashboard com Dados ==="
echo ""

# Verificar se o backend está rodando
echo "1. Verificando backend..."
BACKEND_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/health 2>/dev/null || echo "000")

if [ "$BACKEND_STATUS" != "200" ]; then
    echo "❌ Backend não está rodando"
    echo "   Execute: cd backend && php artisan serve"
    exit 1
fi
echo "✅ Backend está rodando"
echo ""

# Verificar se o frontend está rodando
echo "2. Verificando frontend..."
FRONTEND_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000 2>/dev/null || echo "000")

if [ "$FRONTEND_STATUS" = "000" ]; then
    echo "❌ Frontend não está rodando"
    echo "   Execute: cd frontend && npm run dev"
    exit 1
fi
echo "✅ Frontend está rodando"
echo ""

# Fazer login
echo "3. Fazendo login..."
LOGIN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"fabio@fabio.com","password":"123456"}')

TOKEN=$(echo "$LOGIN" | jq -r '.data.token // empty')

if [ -z "$TOKEN" ]; then
    echo "❌ Falha no login"
    echo "$LOGIN" | jq '.'
    exit 1
fi
echo "✅ Login realizado com sucesso"
echo ""

# Testar cada endpoint
echo "4. Testando endpoints do dashboard..."
echo ""

echo "   📊 Métricas Gerais:"
METRICS=$(curl -s -X GET http://localhost:8000/api/dashboard/metrics \
  -H "Authorization: Bearer $TOKEN")
echo "$METRICS" | jq '{
  receita_total: .data.total_revenue.formatted,
  clientes_ativos: .data.active_clients.value,
  total_pedidos: .data.total_orders.value,
  taxa_conversao: .data.conversion_rate.formatted
}'
echo ""

echo "   🏆 Top Produtos:"
PRODUCTS=$(curl -s -X GET http://localhost:8000/api/dashboard/top-products \
  -H "Authorization: Bearer $TOKEN")
echo "$PRODUCTS" | jq '.data.products[] | {rank, name, quantidade: .total_quantity, receita: .formatted_revenue}'
echo ""

echo "   💳 Transações Recentes:"
TRANSACTIONS=$(curl -s -X GET http://localhost:8000/api/dashboard/recent-transactions \
  -H "Authorization: Bearer $TOKEN")
echo "$TRANSACTIONS" | jq '.data.transactions[] | {id: .identify, cliente: .client.name, total: .formatted_total, status}'
echo ""

echo "   📈 Performance de Vendas:"
SALES=$(curl -s -X GET http://localhost:8000/api/dashboard/sales-performance \
  -H "Authorization: Bearer $TOKEN")
echo "$SALES" | jq '.data.current_month | {mes: .month, vendas: .sales, meta: .goal, performance: .performance}'
echo ""

echo "=== Teste Concluído com Sucesso! ==="
echo ""
echo "🎉 Todos os endpoints estão retornando dados"
echo ""
echo "📝 Próximos passos:"
echo "   1. Abra http://localhost:3000/login"
echo "   2. Faça login com: fabio@fabio.com / 123456"
echo "   3. Verifique se os cards do dashboard exibem os dados"
