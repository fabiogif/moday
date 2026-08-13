#!/bin/bash

echo "🔍 Testando configuração CORS..."
echo ""

# URL do backend
BACKEND_URL="https://orca-app-7hejo.ondigitalocean.app"
FRONTEND_ORIGIN="https://moday-nine.vercel.app"

echo "📡 Testando requisição OPTIONS (preflight) para /api/auth/login..."
echo "Origin: $FRONTEND_ORIGIN"
echo ""

# Teste de preflight request
curl -X OPTIONS "$BACKEND_URL/api/auth/login" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  -v 2>&1 | grep -E "(Access-Control|HTTP/|Origin)"

echo ""
echo "✅ Se você ver 'Access-Control-Allow-Origin: $FRONTEND_ORIGIN' acima, a configuração está correta!"
echo ""

# Teste de requisição real (sem autenticação)
echo "📡 Testando requisição POST para /api/auth/login..."
curl -X POST "$BACKEND_URL/api/auth/login" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  -v 2>&1 | grep -E "(Access-Control|HTTP/|Origin)" | head -10

echo ""
echo "🎯 Teste concluído!"
