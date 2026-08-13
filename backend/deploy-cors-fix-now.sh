#!/bin/bash

echo "=========================================="
echo "CORS FIX DEPLOYMENT SCRIPT"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}This script will:${NC}"
echo "1. Commit the CORS fixes to git"
echo "2. Push to the remote repository"
echo "3. Provide instructions for deploying to production"
echo ""

# Show what will be committed
echo -e "${YELLOW}Files to be committed:${NC}"
git status --short
echo ""

# Ask for confirmation
read -p "Do you want to commit and push these changes? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo -e "${RED}Deployment cancelled.${NC}"
    exit 1
fi

# Commit changes
echo ""
echo -e "${GREEN}Committing changes...${NC}"
git add app/Http/Middleware/CustomCorsMiddleware.php
git add public/.htaccess
git add CORS_FIX_PRODUCTION.md
git add test-cors-fix.sh
git add deploy-cors-fix-now.sh

git commit -m "Fix CORS: Remove wildcard from .htaccess and add production frontend URL

- Removed Access-Control-Allow-Origin wildcard (*) from public/.htaccess
  The wildcard was conflicting with credentials mode
- Added production frontend URL to CustomCorsMiddleware
- Added support for FRONTEND_URL environment variable
- Added documentation and test scripts

Fixes CORS error when frontend uses credentials: 'include'"

# Push to remote
echo ""
echo -e "${GREEN}Pushing to remote repository...${NC}"
git push origin main

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}=========================================="
    echo "✓ Changes pushed successfully!"
    echo "==========================================${NC}"
    echo ""
    echo -e "${YELLOW}NEXT STEPS - Deploy to Production Server:${NC}"
    echo ""
    echo "1. SSH into your production server:"
    echo "   ssh your-user@your-server"
    echo ""
    echo "2. Navigate to your backend directory:"
    echo "   cd /path/to/your/backend"
    echo ""
    echo "3. Pull the latest changes:"
    echo "   git pull origin main"
    echo ""
    echo "4. Update the .env file (add this line if not present):"
    echo "   FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app"
    echo ""
    echo "5. Clear Laravel cache:"
    echo "   php artisan config:clear"
    echo "   php artisan cache:clear"
    echo ""
    echo "6. Restart web server (choose one):"
    echo "   sudo systemctl restart php8.2-fpm"
    echo "   sudo systemctl restart nginx"
    echo "   # OR"
    echo "   sudo systemctl restart apache2"
    echo ""
    echo -e "${YELLOW}7. Test the fix:${NC}"
    echo "   curl -i -X OPTIONS \\"
    echo "     -H 'Origin: https://clownfish-app-rr5rv.ondigitalocean.app' \\"
    echo "     -H 'Access-Control-Request-Method: POST' \\"
    echo "     https://orca-app-7hejo.ondigitalocean.app/api/auth/login"
    echo ""
    echo -e "${GREEN}Expected response headers:${NC}"
    echo "   Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app"
    echo "   Access-Control-Allow-Credentials: true"
    echo ""
else
    echo ""
    echo -e "${RED}Failed to push changes. Please check the error and try again.${NC}"
    exit 1
fi
