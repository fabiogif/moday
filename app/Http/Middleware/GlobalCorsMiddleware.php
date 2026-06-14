<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GlobalCorsMiddleware
{
    /**
     * Handle an incoming request - Add CORS headers to ALL requests
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allowed origins - HARDCODED para garantir que funcione
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'https://localhost:3000',
            'https://localhost:3001',
            'https://moday-nine.vercel.app',
            'https://moday-frontend.vercel.app',
            'https://clownfish-app-rr5rv.ondigitalocean.app',
            'https://orca-app-7hejo.ondigitalocean.app',
        ];
        
        $origin = $request->header('Origin');
        
        // Log apenas em ambiente de desenvolvimento para evitar vazamento de dados sensíveis
        if (config('app.debug', false)) {
            Log::debug('GlobalCorsMiddleware executing', [
            'origin' => $origin,
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'allowed' => in_array($origin, $allowedOrigins),
                // Removido 'all_headers' para evitar exposição de tokens e credenciais
        ]);
        }
        
        // INTERCEPTAR TODAS as requisições OPTIONS IMEDIATAMENTE
        if ($request->getMethod() === 'OPTIONS') {
            // Log apenas em desenvolvimento
            if (config('app.debug', false)) {
                Log::debug('OPTIONS request intercepted', [
                'origin' => $origin,
                'path' => $request->path(),
                'allowed' => in_array($origin, $allowedOrigins)
            ]);
            }
            
            if (in_array($origin, $allowedOrigins)) {
                $response = response('', 204)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, Access-Control-Request-Method, Access-Control-Request-Headers')
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header('Access-Control-Max-Age', '86400');
                
                // Log apenas em desenvolvimento
                if (config('app.debug', false)) {
                    Log::debug('OPTIONS response created', [
                    'status' => 204,
                        // Removido 'headers' para evitar exposição de informações sensíveis
                ]);
                }
                
                return $response;
            }
            
            // For disallowed origins, still return 204 but without CORS headers
            return response('', 204);
        }
        
        // Process the request
        $response = $next($request);
        
        // Add CORS headers to the response if origin is allowed
        if (in_array($origin, $allowedOrigins)) {
            // FORÇAR remoção de TODOS os headers CORS (incluindo duplicados)
            $allHeaders = $response->headers->all();
            foreach ($allHeaders as $headerName => $headerValues) {
                if (str_starts_with(strtolower($headerName), 'access-control-')) {
                    $response->headers->remove($headerName);
                }
            }
            
            // Adicionar headers CORS limpos
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        
        return $response;
    }
}
