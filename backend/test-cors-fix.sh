#!/bin/bash

# Test CORS configuration with the production frontend URL
echo "Testing CORS with production frontend URL..."
echo ""

# Test preflight request (OPTIONS)
echo "1. Testing preflight request (OPTIONS):"
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  http://localhost:8000/api/auth/login

echo ""
echo "---"
echo ""

# Test actual request
echo "2. Testing actual POST request:"
curl -i -X POST \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  http://localhost:8000/api/auth/login

echo ""
echo "---"
echo ""
echo "Look for these headers in the response:"
echo "- Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app"
echo "- Access-Control-Allow-Credentials: true"
echo "- Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH"
