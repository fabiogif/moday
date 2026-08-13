# CORS Fix - Complete Deployment Guide

## Current Error
```
No 'Access-Control-Allow-Origin' header is present on the requested resource
```

This means the Laravel middleware is NOT sending CORS headers. The wildcard issue is fixed (good!), but now headers aren't being sent at all.

## Why This Happens
The production server either:
1. Hasn't pulled the latest code with the production URL
2. Has old cached configuration
3. The middleware isn't matching the origin (debugging will tell us)

## What We Fixed (Version 2)

### 1. Added Debugging
Added logging to `CustomCorsMiddleware.php` to help diagnose why origins aren't matching:
```php
\Log::info('CORS Debug', [
    'origin' => $origin,
    'allowed_origins' => $allowedOrigins,
    'method' => $request->getMethod(),
    'is_allowed' => in_array($origin, $allowedOrigins)
]);
```

### 2. Improved Error Handling
- Changed OPTIONS response for disallowed origins from `200` to `403`
- Added null checks for `$origin` before comparison
- This helps identify when requests don't have an Origin header

### 3. Removed Wildcard from .htaccess
- Already done in previous version
- Ensures Apache doesn't override Laravel

## Deployment Steps

### STEP 1: Commit and Push (Local Machine)
```bash
# Review changes
git status
git diff

# Commit
git add app/Http/Middleware/CustomCorsMiddleware.php
git add public/.htaccess  
git add deploy-to-production.sh
git add diagnose-cors.sh
git commit -m "Fix CORS: Add debugging and improve error handling"
git push origin main
```

### STEP 2: Deploy to Production Server

#### Option A: Use Automated Script (Recommended)
```bash
# SSH to production server
ssh your-user@your-server

# Navigate to backend directory
cd /path/to/backend

# Download and run the deployment script
curl -O https://raw.githubusercontent.com/your-repo/backend/main/deploy-to-production.sh
chmod +x deploy-to-production.sh
./deploy-to-production.sh
```

#### Option B: Manual Steps
```bash
# SSH to production server
ssh your-user@your-server

# Navigate to backend
cd /path/to/backend

# Pull latest code
git pull origin main

# Add FRONTEND_URL to .env (if not exists)
echo "FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app" >> .env

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan config:cache
php artisan route:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
# OR
sudo systemctl restart apache2
```

### STEP 3: Run Diagnostics
```bash
# On production server
./diagnose-cors.sh
```

This will tell you:
- ✓ If the code was updated correctly
- ✓ If .env has FRONTEND_URL
- ✓ If .htaccess is clean
- ✓ What CORS headers are being sent
- ✓ Any errors in the logs

### STEP 4: Check Laravel Logs
After running diagnose, try to log in from your frontend, then check:
```bash
tail -f storage/logs/laravel.log | grep "CORS"
```

You should see entries like:
```
CORS Debug: {
  "origin": "https://clownfish-app-rr5rv.ondigitalocean.app",
  "allowed_origins": [...],
  "method": "OPTIONS",
  "is_allowed": true
}
```

## Troubleshooting

### If is_allowed is false:
The origin isn't matching. Check:
1. Is FRONTEND_URL set in production .env?
2. Is the URL exactly `https://clownfish-app-rr5rv.ondigitalocean.app`? (no trailing slash)
3. Did you run `php artisan config:cache`?

### If no CORS Debug logs appear:
The middleware isn't running. Check:
1. Did `git pull` work?
2. Is the middleware registered in `bootstrap/app.php`?
3. Did you clear route cache?

### If still seeing wildcard (*):
.htaccess wasn't updated. Check:
1. Run `cat public/.htaccess` on production
2. Should NOT contain `Access-Control-Allow-Origin`
3. If it does, manually remove that section

### If headers appear but wrong origin:
Check nginx/apache config for CORS headers:
```bash
# For nginx
cat /etc/nginx/sites-available/your-site
grep -i "cors\|access-control" /etc/nginx/sites-available/your-site

# For apache  
cat /etc/apache2/sites-available/your-site.conf
grep -i "cors\|access-control" /etc/apache2/sites-available/your-site.conf
```

## Quick Test Commands

### Test from anywhere:
```bash
# Test OPTIONS (preflight)
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login

# Test actual POST
curl -i -X POST \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Content-Type: application/json" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login
```

### Expected Response Headers:
```
HTTP/1.1 200 OK
Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 86400
```

## Files Changed in This Version
1. `app/Http/Middleware/CustomCorsMiddleware.php` - Added debugging and improved checks
2. `public/.htaccess` - Removed wildcard (already done)
3. `deploy-to-production.sh` - New automated deployment script
4. `diagnose-cors.sh` - New diagnostic script

## After Fix is Working
Once everything works, you can remove the debug logging:
1. Edit `app/Http/Middleware/CustomCorsMiddleware.php`
2. Remove or comment out the `\Log::info('CORS Debug', ...)` section
3. Commit and deploy again

## Support
If still having issues after following all steps:
1. Run `./diagnose-cors.sh` and share the output
2. Share the last 20 lines of Laravel log: `tail -20 storage/logs/laravel.log`
3. Share the response from the curl test command
