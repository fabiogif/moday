<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="CSRF",
 *     description="Endpoints para gerenciamento de tokens CSRF"
 * )
 */
class CsrfTokenController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/csrf-token",
     *     summary="Obter token CSRF",
     *     description="Retorna um token CSRF válido para ser usado em requisições que modificam dados",
     *     tags={"CSRF"},
     *     @OA\Response(
     *         response=200,
     *         description="Token CSRF gerado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="csrf_token", type="string", example="XYZ123..."),
     *             @OA\Property(property="expires_at", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function getToken(Request $request): JsonResponse
    {
        $token = csrf_token();
        
        return response()->json([
            'csrf_token' => $token,
            'expires_at' => now()->addMinutes(config('session.lifetime', 120))->toISOString(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/csrf-token/verify",
     *     summary="Verificar token CSRF",
     *     description="Verifica se um token CSRF é válido",
     *     tags={"CSRF"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="XYZ123...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token válido",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token CSRF válido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Token inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token CSRF inválido")
     *         )
     *     )
     * )
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $isValid = hash_equals(csrf_token(), $request->input('token'));

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Token CSRF válido' : 'Token CSRF inválido',
        ], $isValid ? 200 : 422);
    }
}
