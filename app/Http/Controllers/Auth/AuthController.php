<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\Controller as ApiController;
use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\UpdateUserProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

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
     *                 @OA\Property(property="expires_in", type="integer", example=7200)
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
            
            Log::info('AuthController login attempt', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'request_id' => uniqid('auth_')
            ]);
            
            $result = $this->authService->login($credentials);
        
            if (!$result['success']) {
                Log::warning('AuthController login failed', [
                    'email' => $credentials['email'],
                    'reason' => $result['message'],
                    'ip' => $request->ip(),
                    'request_id' => uniqid('auth_')
                ]);
                
                // Retornar erro seguindo o padrão da aplicação
                return ApiResponseClass::validationError(
                    ['email' => [$result['message']]],
                    $result['message']
                );
            }

            Log::info('AuthController login successful', [
                'user_id' => $result['user']->id,
                'email' => $result['user']->email,
                'ip' => $request->ip(),
                'request_id' => uniqid('auth_')
            ]);

            $ttlMinutes = config('jwt.ttl', 120);

            // Criar cookie HttpOnly seguro (TTL configurado)
            $cookie = cookie(
                'auth_token',
                $result['token'],
                $ttlMinutes,
                '/',
                null,
                true, // secure (HTTPS)
                true  // httpOnly
            );

            return ApiResponseClass::sendResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'expires_in' => $ttlMinutes * 60,
                'trial_status' => $result['trial_status'] ?? null
            ], 'Login realizado com sucesso')->withCookie($cookie);

        } catch (\Exception $e) {
            Log::error('AuthController internal error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'request_id' => uniqid('auth_')
            ]);
            
            return ApiResponseClass::throw($e, 'Erro ao realizar login');
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
     *                 @OA\Property(property="expires_in", type="integer", example=7200)
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
                'expires_in' => config('jwt.ttl', 120) * 60
            ], 'Usuário registrado com sucesso', 201);

        } catch (\Exception $e) {
            Log::error('Erro no registro: ' . $e->getMessage());
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
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Não autorizado")
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Não autorizado');
            }
            
            // Carregar relacionamentos diretamente
            $user->tenant;
            $user->profiles;
            
            return ApiResponseClass::sendResponse(
                new UserResource($user),
                'Dados do usuário recuperados com sucesso'
            );

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados do usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao buscar dados do usuário');
        }
    }

    /**
     * Atualiza nome, email e opcionalmente a senha do usuário autenticado.
     */
    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Não autorizado');
            }

            $data = $request->validated();

            if (!empty($data['new_password'])) {
                if (!Hash::check($data['current_password'], $user->password)) {
                    return ApiResponseClass::validationError(
                        ['current_password' => ['A senha atual está incorreta.']],
                        'A senha atual está incorreta.'
                    );
                }

                $data['password'] = $data['new_password'];
            }

            $result = $this->authService->updateProfile($user, [
                'name' => $data['name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => $data['password']] : []),
            ]);

            return ApiResponseClass::sendResponse(
                new UserResource($result['user']),
                $result['message']
            );
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil do usuário: '.$e->getMessage());

            return ApiResponseClass::throw($e, 'Erro ao atualizar informações da conta');
        }
    }

    /**
     * Logout do usuário
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();
            
            // Limpar cookie HttpOnly
            $cookie = cookie('auth_token', '', -1, '/', null, true, true);
            
            return ApiResponseClass::sendResponse('', 'Logout realizado com sucesso')
                ->withCookie($cookie);

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
            $newToken = JWTAuth::refresh();
            $expiresIn = JWTAuth::factory()->getTTL() * 60;

            $cookie = cookie(
                'auth_token',
                $newToken,
                $expiresIn / 60,
                '/',
                null,
                true,
                true
            );

            return ApiResponseClass::sendResponse([
                'token' => $newToken,
                'expires_in' => $expiresIn,
            ], 'Token renovado com sucesso')->withCookie($cookie);

        } catch (TokenExpiredException $e) {
            return ApiResponseClass::unauthorized('Token expirado, realize login novamente');
        } catch (TokenInvalidException|JWTException $e) {
            return ApiResponseClass::unauthorized('Token inválido');
        } catch (\Exception $e) {
            Log::error('Erro ao renovar token: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao renovar token');
        }
    }

    /**
     * Esqueci minha senha
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->sendPasswordResetLink($request->validated()['email']);
            
            if (!$result['success']) {
                return ApiResponseClass::sendResponse('', $result['message'], 422);
            }
            
            return ApiResponseClass::sendResponse('', $result['message']);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar link de recuperação: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao enviar link de recuperação');
        }
    }

    /**
     * Resetar senha
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->resetPassword($request->validated());
            
            if (!$result['success']) {
                return ApiResponseClass::unauthorized($result['message']);
            }
            
            return ApiResponseClass::sendResponse('', $result['message']);

        } catch (\Exception $e) {
            Log::error('Erro ao resetar senha: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao resetar senha');
        }
    }
}
