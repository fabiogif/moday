<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificação de CSRF em requisições API
 * 
 * Este middleware verifica o token CSRF em requisições que modificam dados.
 * O token pode ser enviado via header X-CSRF-TOKEN ou no corpo da requisição.
 */
class VerifyCsrfTokenApi
{
    /**
     * URIs que devem ser excluídas da verificação CSRF.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/auth/login',
        'api/auth/register',
        'api/auth/forgot-password',
        'api/auth/refresh',
        'api/health',
        'api/csrf-token',
        'api/csrf-token/verify',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apenas verifica CSRF em requisições que modificam dados
        if ($this->shouldVerifyCsrf($request)) {
            $token = $this->getTokenFromRequest($request);

            if (!$this->tokensMatch($token)) {
                return response()->json([
                    'message' => 'Token CSRF inválido ou ausente.',
                    'error' => 'csrf_token_mismatch'
                ], 419);
            }
        }

        return $next($request);
    }

    /**
     * Determina se a requisição deve ter o CSRF verificado
     */
    protected function shouldVerifyCsrf(Request $request): bool
    {
        // Não verifica em métodos GET, HEAD, OPTIONS
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return false;
        }

        // Verifica se a URI está na lista de exceções
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtém o token CSRF da requisição
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Tenta pegar do header primeiro
        $token = $request->header('X-CSRF-TOKEN');

        // Se não tiver no header, tenta do corpo da requisição
        if (!$token) {
            $token = $request->input('_token') ?? $request->input('csrf_token');
        }

        return $token;
    }

    /**
     * Verifica se os tokens conferem
     */
    protected function tokensMatch(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        return hash_equals(csrf_token(), $token);
    }
}
