<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     * Adiciona headers de segurança HTTP para proteção contra ataques comuns.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // X-Content-Type-Options: Previne MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // X-Frame-Options: Previne clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // X-XSS-Protection: Habilita proteção XSS do navegador (legacy, mas ainda útil)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Referrer-Policy: Controla quanto referrer information é enviado
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions-Policy: Controla features do navegador (antigo Feature-Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // HSTS (HTTP Strict Transport Security) - apenas em HTTPS
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}

