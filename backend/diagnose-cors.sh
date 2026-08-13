#!/bin/bash

# CORS Diagnostic Script - Run on Production Server
# This helps identify why CORS isn't working

echo "=========================================="
echo "CORS Configuration Diagnostic"
echo "=========================================="
echo ""

# 1. Check if CustomCorsMiddleware has the production URL
echo "1. Checking CustomCorsMiddleware.php for production URL..."
if grep -q "clownfish-app-rr5rv.ondigitalocean.app" app/Http/Middleware/CustomCorsMiddleware.php; then
    echo "   ✓ Production URL found in middleware"
else
    echo "   ✗ Production URL NOT found in middleware"
    echo "   This file needs to be updated!"
fi
echo ""

# 2. Check .env for FRONTEND_URL
echo "2. Checking .env for FRONTEND_URL..."
if grep -q "FRONTEND_URL=" .env; then
    echo "   ✓ FRONTEND_URL exists:"
    grep "FRONTEND_URL=" .env
else
    echo "   ✗ FRONTEND_URL not set in .env"
fi
echo ""

# 3. Check .htaccess for wildcard CORS
echo "3. Checking public/.htaccess for CORS headers..."
if grep -q "Access-Control-Allow-Origin" public/.htaccess; then
    echo "   ✗ .htaccess contains CORS headers (this is BAD):"
    grep -A 5 "Access-Control-Allow-Origin" public/.htaccess
    echo "   These should be REMOVED!"
else
    echo "   ✓ .htaccess does not contain CORS headers (good)"
fi
echo ""

# 4. Check if middleware is registered
echo "4. Checking if CustomCorsMiddleware is registered..."
if grep -q "CustomCorsMiddleware" bootstrap/app.php; then
    echo "   ✓ Middleware is registered in bootstrap/app.php"
else
    echo "   ✗ Middleware NOT found in bootstrap/app.php"
fi
echo ""

# 5. Test actual CORS response
echo "5. Testing CORS response from production API..."
echo "   Sending OPTIONS request to https://orca-app-7hejo.ondigitalocean.app/api/auth/login"
echo ""
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login

echo ""
echo ""
echo "6. Analysis:"
echo "   Look for these headers in the response above:"
echo "   - Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app"
echo "   - Access-Control-Allow-Credentials: true"
echo ""
echo "   If you see:"
echo "   - Access-Control-Allow-Origin: * → .htaccess still has wildcard"
echo "   - No CORS headers at all → Middleware not running or origin not matched"
echo "   - HTTP 403 → Origin not in allowed list"
echo ""

# 6. Check Laravel logs for CORS debug
echo "7. Recent Laravel logs (looking for CORS debug info)..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "   Last CORS-related logs:"
    tail -50 storage/logs/laravel.log | grep -i "cors" | tail -5 || echo "   No CORS logs found"
else
    echo "   No log file found"
fi
echo ""

echo "=========================================="
echo "Diagnostic Complete"
echo "=========================================="
