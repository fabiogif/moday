<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user->email_verified_at !== null) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'error' => 'email_unverified',
            'message' => 'Confirme seu e-mail para continuar usando o painel.',
        ], 403);
    }
}
