#!/bin/bash

# Test script to verify metrics endpoint

API_URL="http://localhost:8000"

# Step 1: Login
echo "=== Logging in ==="
LOGIN_RESPONSE=$(curl -s -X POST "${API_URL}/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "fabio@fabio.com",
    "password": "123456"
  }')

echo "$LOGIN_RESPONSE" | jq '.'

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token // empty')

if [ -z "$TOKEN" ]; then
  echo "ERROR: Failed to get token"
  exit 1
fi

echo ""
echo "=== Token obtained ==="
echo "$TOKEN"

# Step 2: Get Metrics
echo ""
echo "=== Getting Metrics ==="
METRICS_RESPONSE=$(curl -s -X GET "${API_URL}/api/dashboard/metrics" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "$METRICS_RESPONSE" | jq '.'

# Step 3: Get Sales Performance
echo ""
echo "=== Getting Sales Performance ==="
SALES_RESPONSE=$(curl -s -X GET "${API_URL}/api/dashboard/sales-performance" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "$SALES_RESPONSE" | jq '.'

# Step 4: Get Recent Transactions
echo ""
echo "=== Getting Recent Transactions ==="
TRANSACTIONS_RESPONSE=$(curl -s -X GET "${API_URL}/api/dashboard/recent-transactions" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "$TRANSACTIONS_RESPONSE" | jq '.'

# Step 5: Get Top Products
echo ""
echo "=== Getting Top Products ==="
PRODUCTS_RESPONSE=$(curl -s -X GET "${API_URL}/api/dashboard/top-products" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "$PRODUCTS_RESPONSE" | jq '.'
