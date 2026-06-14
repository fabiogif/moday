<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = array_filter([
            'http://localhost:3000',
            'http://localhost:3001',
            'https://localhost:3000',
            'https://localhost:3001',
            'https://moday-nine.vercel.app',
            'https://moday-frontend.vercel.app',
            'https://clownfish-app-rr5rv.ondigitalocean.app',
            'https://orca-app-7hejo.ondigitalocean.app',
            env('FRONTEND_URL'),
            env('ADDITIONAL_CORS_ORIGIN'),
        ]);

        $origin = $request->headers->get('Origin');
        
        // Log for debugging (remove in production after testing)
        \Log::info('CORS Debug', [
            'origin' => $origin,
            'allowed_origins' => $allowedOrigins,
            'method' => $request->getMethod(),
            'is_allowed' => in_array($origin, $allowedOrigins)
        ]);
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            if ($origin && in_array($origin, $allowedOrigins)) {
                return response('', 200)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header('Access-Control-Max-Age', '86400');
            }
            
            // Return 403 for disallowed origins instead of 200 without headers
            return response('CORS not allowed', 403);
        }
        
        // Handle actual requests
        if ($origin && in_array($origin, $allowedOrigins)) {
            $response = $next($request);
            
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
            
            return $response;
        }

        return $next($request);
    }
}

