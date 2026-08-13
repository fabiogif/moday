<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request.
     * Adiciona um Request ID único para rastreabilidade de requisições.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obter ou gerar Request ID
        $requestId = $request->header('X-Request-ID') ?? Str::uuid()->toString();
        
        // Adicionar ao request para uso posterior
        $request->headers->set('X-Request-ID', $requestId);
        
        // Adicionar ao contexto de log para rastreabilidade
        Log::withContext(['request_id' => $requestId]);
        
        // Processar requisição
        $response = $next($request);
        
        // Adicionar header na resposta para o cliente
        $response->headers->set('X-Request-ID', $requestId);
        
        return $response;
    }
}

