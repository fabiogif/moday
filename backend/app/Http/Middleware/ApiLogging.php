<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLogging
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log da requisição
        Log::info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()?->tenant_id,
            'request_data' => $this->sanitizeRequestData($request->all())
        ]);
        
        $response = $next($request);
        
        // Log da resposta
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info('API Response', [
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()?->tenant_id,
        ]);
        
        return $response;
    }
    
    /**
     * Remove dados sensíveis do log
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }
        
        return $data;
    }
}
