<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], 404);
            }

            // Verifica se o usuário está ativo
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário inativo'
                ], 403);
            }

            Auth::shouldUse('api');
            Auth::guard('api')->setUser($user);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido'
            ], 401);

        } catch (\UnexpectedValueException $e) {
            Log::warning('JwtMiddleware: token malformado', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);

        } catch (\Throwable $e) {
            Log::error('JwtMiddleware: erro de autorização', [
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de autorização'
            ], 401);
        }

        return $next($request);
    }
}
