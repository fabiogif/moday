#!/bin/bash

# Test script to validate dashboard authentication and data loading

API_URL="http://localhost:8000"
FRONTEND_URL="http://localhost:3000"

echo "=== Testing Dashboard Authentication Flow ==="
echo ""

# Step 1: Test Login
echo "1. Testing login endpoint..."
LOGIN_RESPONSE=$(curl -s -X POST "${API_URL}/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"fabio@fabio.com","password":"123456"}')

echo "$LOGIN_RESPONSE" | jq '.'

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')

if [ "$TOKEN" == "null" ] || [ -z "$TOKEN" ]; then
  echo "❌ Login failed - no token received"
  exit 1
fi

echo "✅ Login successful - Token received"
echo ""

# Step 2: Test Dashboard Metrics
echo "2. Testing dashboard metrics endpoint..."
METRICS_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  "${API_URL}/api/dashboard/metrics")

echo "$METRICS_RESPONSE" | jq '.'

if echo "$METRICS_RESPONSE" | jq -e '.success' > /dev/null 2>&1; then
  echo "✅ Dashboard metrics loaded successfully"
else
  echo "❌ Dashboard metrics failed to load"
  exit 1
fi
echo ""

# Step 3: Test Top Products
echo "3. Testing top products endpoint..."
PRODUCTS_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  "${API_URL}/api/dashboard/top-products")

echo "$PRODUCTS_RESPONSE" | jq '.'

if echo "$PRODUCTS_RESPONSE" | jq -e '.success' > /dev/null 2>&1; then
  echo "✅ Top products loaded successfully"
else
  echo "❌ Top products failed to load"
  exit 1
fi
echo ""

# Step 4: Test Recent Transactions
echo "4. Testing recent transactions endpoint..."
TRANSACTIONS_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  "${API_URL}/api/dashboard/recent-transactions")

echo "$TRANSACTIONS_RESPONSE" | jq '.'

if echo "$TRANSACTIONS_RESPONSE" | jq -e '.success' > /dev/null 2>&1; then
  echo "✅ Recent transactions loaded successfully"
else
  echo "❌ Recent transactions failed to load"
  exit 1
fi
echo ""

echo "=== All Tests Passed! ==="
echo ""
echo "Next steps:"
echo "1. Open browser to ${FRONTEND_URL}/login"
echo "2. Login with fabio@fabio.com / 123456"
echo "3. Navigate to ${FRONTEND_URL}/dashboard"
echo "4. Verify all cards show data"
