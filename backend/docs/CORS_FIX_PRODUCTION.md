# CORS Fix for Production Frontend

## Problem
The frontend application at `https://clownfish-app-rr5rv.ondigitalocean.app` was unable to make authenticated requests to the backend API at `https://orca-app-7hejo.ondigitalocean.app` due to CORS policy blocking.

**Error Message:**
```
Access to fetch at 'https://orca-app-7hejo.ondigitalocean.app/api/auth/login' 
from origin 'https://clownfish-app-rr5rv.ondigitalocean.app' has been blocked 
by CORS policy: Response to preflight request doesn't pass access control check: 
The value of the 'Access-Control-Allow-Origin' header in the response must not 
be the wildcard '*' when the request's credentials mode is 'include'.
```

## Root Cause
**TWO ISSUES were found:**

1. **`.htaccess` file was setting wildcard CORS headers** - The `public/.htaccess` file had `Header always set Access-Control-Allow-Origin "*"` which was overriding the Laravel middleware and causing the wildcard error.

2. **`CustomCorsMiddleware` missing production URL** - The middleware had a hardcoded list of allowed origins that did not include the production frontend URL.

## Solution
Updated the following files:

### 1. public/.htaccess (CRITICAL FIX)
**File:** `public/.htaccess`

**Changes:**
- **REMOVED the entire `<IfModule mod_headers.c>` section** that was setting wildcard CORS headers
- The removed section was:
  ```apache
  <IfModule mod_headers.c>
      # CORS Headers
      Header always set Access-Control-Allow-Origin "*"
      Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
      Header always set Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept, Authorization"
      Header always set Access-Control-Max-Age "3600"
  </IfModule>
  ```
- This was the PRIMARY cause of the wildcard error
- CORS is now handled exclusively by Laravel middleware

### 2. CustomCorsMiddleware.php
**File:** `app/Http/Middleware/CustomCorsMiddleware.php`

**Changes:**
- Added production frontend URL to allowed origins: `https://clownfish-app-rr5rv.ondigitalocean.app`
- Made the middleware use environment variables for flexibility
- Changed from hardcoded array to `array_filter()` to remove empty values

### 3. .env file
**File:** `.env`

**Changes:**
- Added `FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app`
- Fixed malformed `SESSION_LIFETIME` variable (was `    =120`, now `SESSION_LIFETIME=120`)

## Deployment Steps

### For Production Server

1. **Update the code on the production server:**
   ```bash
   cd /path/to/your/backend
   git pull origin main
   ```

2. **Update the production .env file:**
   Add or update this line in your production `.env` file:
   ```
   FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app
   ```

3. **Clear Laravel cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan config:cache
   ```

4. **Restart your web server/PHP-FPM:**
   ```bash
   # For nginx with PHP-FPM:
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart nginx
   
   # OR for Apache:
   sudo systemctl restart apache2
   ```

### Alternative: Environment Variable Only
If you prefer to not hardcode the frontend URL in the middleware, you can rely solely on the `FRONTEND_URL` environment variable. Just ensure it's set in your production `.env` file.

## Testing

### Local Testing
Run the test script:
```bash
./test-cors-fix.sh
```

Look for these headers in the response:
- `Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app`
- `Access-Control-Allow-Credentials: true`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH`

### Production Testing
Test with curl from your local machine:
```bash
# Test preflight request
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login
```

The response should include:
- `Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app`
- `Access-Control-Allow-Credentials: true`

## Files Changed
1. **`public/.htaccess`** - **CRITICAL:** Removed wildcard CORS headers that were causing the error
2. **`app/Http/Middleware/CustomCorsMiddleware.php`** - Added production frontend URL
3. **`.env`** - Added FRONTEND_URL variable and fixed SESSION_LIFETIME

## Important Notes
- The middleware is configured to support credentials (`credentials: 'include'`) which is required for cookie-based authentication
- The `Access-Control-Allow-Origin` header is set to the specific origin, not a wildcard, which is required when using credentials
- The middleware handles both preflight (OPTIONS) requests and actual requests

## Verification Checklist
- [ ] Code deployed to production server
- [ ] Production `.env` file updated with `FRONTEND_URL`
- [ ] Laravel cache cleared on production
- [ ] Web server/PHP-FPM restarted
- [ ] CORS headers verified with curl
- [ ] Frontend login tested from browser
- [ ] Browser console shows no CORS errors
