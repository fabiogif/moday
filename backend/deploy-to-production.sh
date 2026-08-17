#!/bin/bash

# Production Server CORS Fix Deployment Script
# Run this script ON YOUR PRODUCTION SERVER

set -e  # Exit on error

echo "=========================================="
echo "CORS Fix - Production Server Deployment"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Are you in the Laravel root directory?${NC}"
    exit 1
fi

echo -e "${BLUE}Step 1: Pulling latest code...${NC}"
git pull origin main
if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to pull code. Please check git status.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Code updated${NC}"
echo ""

echo -e "${BLUE}Step 2: Checking .env file for FRONTEND_URL...${NC}"
if grep -q "FRONTEND_URL=" .env; then
    echo -e "${GREEN}✓ FRONTEND_URL already exists in .env${NC}"
    grep "FRONTEND_URL=" .env
else
    echo -e "${YELLOW}Adding FRONTEND_URL to .env...${NC}"
    echo "FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app" >> .env
    echo -e "${GREEN}✓ FRONTEND_URL added${NC}"
fi
echo ""

echo -e "${BLUE}Step 3: Verifying CustomCorsMiddleware.php...${NC}"
if grep -q "https://clownfish-app-rr5rv.ondigitalocean.app" app/Http/Middleware/CustomCorsMiddleware.php; then
    echo -e "${GREEN}✓ Production frontend URL found in middleware${NC}"
else
    echo -e "${RED}✗ Production frontend URL NOT found in middleware${NC}"
    echo -e "${YELLOW}The git pull may not have updated the file. Check git status.${NC}"
fi
echo ""

echo -e "${BLUE}Step 4: Checking .htaccess file...${NC}"
if grep -q "Access-Control-Allow-Origin" public/.htaccess; then
    echo -e "${RED}✗ WARNING: .htaccess still contains CORS headers${NC}"
    echo -e "${YELLOW}This will cause conflicts. The .htaccess should NOT have CORS headers.${NC}"
else
    echo -e "${GREEN}✓ .htaccess is clean (no CORS headers)${NC}"
fi
echo ""

echo -e "${BLUE}Step 5: Clearing Laravel cache...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

echo -e "${BLUE}Step 6: Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
echo -e "${GREEN}✓ Optimized${NC}"
echo ""

echo -e "${BLUE}Step 7: Restarting services...${NC}"
echo -e "${YELLOW}Attempting to restart PHP-FPM and web server...${NC}"

# Try to restart PHP-FPM (try different versions)
if systemctl list-units --type=service | grep -q "php8.2-fpm"; then
    sudo systemctl restart php8.2-fpm
    echo -e "${GREEN}✓ PHP 8.2 FPM restarted${NC}"
elif systemctl list-units --type=service | grep -q "php8.1-fpm"; then
    sudo systemctl restart php8.1-fpm
    echo -e "${GREEN}✓ PHP 8.1 FPM restarted${NC}"
elif systemctl list-units --type=service | grep -q "php-fpm"; then
    sudo systemctl restart php-fpm
    echo -e "${GREEN}✓ PHP FPM restarted${NC}"
else
    echo -e "${YELLOW}⚠ Could not detect PHP-FPM service${NC}"
fi

# Try to restart web server
if systemctl list-units --type=service | grep -q "nginx"; then
    sudo systemctl restart nginx
    echo -e "${GREEN}✓ Nginx restarted${NC}"
elif systemctl list-units --type=service | grep -q "apache2"; then
    sudo systemctl restart apache2
    echo -e "${GREEN}✓ Apache restarted${NC}"
else
    echo -e "${YELLOW}⚠ Could not detect web server service${NC}"
fi
echo ""

echo -e "${BLUE}Step 8: Testing CORS configuration...${NC}"
echo "Testing OPTIONS request to /api/auth/login..."
RESPONSE=$(curl -s -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login)

echo "$RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app"; then
    echo -e "${GREEN}✓✓✓ CORS is working correctly! ✓✓✓${NC}"
    echo -e "${GREEN}Access-Control-Allow-Origin header is set to the correct origin.${NC}"
elif echo "$RESPONSE" | grep -q "Access-Control-Allow-Origin: \*"; then
    echo -e "${RED}✗✗✗ CORS still has wildcard! ✗✗✗${NC}"
    echo -e "${YELLOW}The .htaccess file may still have the old CORS headers.${NC}"
    echo -e "${YELLOW}Or nginx configuration might be adding headers.${NC}"
else
    echo -e "${RED}✗✗✗ No CORS headers found! ✗✗✗${NC}"
    echo -e "${YELLOW}The middleware may not be working. Check the logs.${NC}"
fi

echo ""
echo -e "${BLUE}Step 9: Checking Laravel logs for CORS debug info...${NC}"
echo "Last 10 CORS-related log entries:"
tail -20 storage/logs/laravel.log | grep -i "cors" || echo "No CORS logs found yet. Try the login again and check logs."
echo ""

echo "=========================================="
echo -e "${GREEN}Deployment Complete!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Try logging in from your frontend"
echo "2. If it still fails, check: tail -f storage/logs/laravel.log"
echo "3. Look for the 'CORS Debug' log entries"
echo ""
