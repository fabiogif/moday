<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\Controller as ApiController;
use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints de autenticação e gerenciamento de usuários"
 * )
 */
class AuthController extends ApiController
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login do usuário",
     *     description="Autentica um usuário e retorna um token JWT",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="teste@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login realizado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="João Silva"),
     *                     @OA\Property(property="email", type="string", example="teste@example.com"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();
            
            $result = $this->authService->login($credentials);
        
            if (!$result['success']) {
                throw ValidationException::withMessages([
                    'email' => [$result['message']]
                ]);
            }

            return ApiResponseClass::sendResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ], 'Login realizado com sucesso');

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro no login: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro interno do servidor');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Registro de novo usuário",
     *     description="Cria um novo usuário e retorna um token JWT",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário registrado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário registrado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="João Silva"),
     *                     @OA\Property(property="email", type="string", example="joao@example.com"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados de validação inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            $result = $this->authService->register($data);
            
            return ApiResponseClass::sendResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ], 'Usuário registrado com sucesso', 201);

        } catch (\Exception $e) {
            Log::error('Erro no registro: ' . $e->getMessage());
            dd($e);
            return ApiResponseClass::throw($e, 'Erro ao registrar usuário');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Informações do usuário logado",
     *     description="Retorna as informações do usuário autenticado",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informações do usuário",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", example="joao@example.com"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="tenant", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Restaurante ABC")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            
            return ApiResponseClass::sendResponse(
                new UserResource($user->load(['tenant', 'profiles.permissions']))
            );

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados do usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao buscar dados do usuário');
        }
    }

    /**
     * Logout do usuário
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();
            
            return ApiResponseClass::sendResponse('', 'Logout realizado com sucesso');

        } catch (\Exception $e) {
            Log::error('Erro no logout: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro no logout');
        }
    }

    /**
     * Refresh do token
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            
            return ApiResponseClass::sendResponse([
                'token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao renovar token: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao renovar token');
        }
    }

    /**
     * Esqueci minha senha
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate(['email' => 'required|email']);
            
            $result = $this->authService->sendPasswordResetLink($request->email);
            
            return ApiResponseClass::sendResponse('', $result['message'], $result['success'] ? 200 : 422);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar link de recuperação: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao enviar link de recuperação');
        }
    }

    /**
     * Resetar senha
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed'
            ]);
            
            $result = $this->authService->resetPassword($request->all());
            
            return ApiResponseClass::sendResponse('', $result['message'], $result['success'] ? 200 : 422);

        } catch (\Exception $e) {
            Log::error('Erro ao resetar senha: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao resetar senha');
        }
    }
}
