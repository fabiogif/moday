<?php

namespace App\Services;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Realiza o login do usuário
     */
    public function login(array $credentials): array
    {
        try {
            Log::info('AuthService login called', ['email' => $credentials['email']]);
            
            // Busca o usuário pelo email
            $user = User::where('email', $credentials['email'])
                       ->with(['tenant', 'profile.permissions'])
                       ->first();

            Log::info('User search result', ['found' => $user ? true : false, 'user_id' => $user?->id]);

            // Verifica se o usuário existe e se a senha está correta
            if (!$user) {
                Log::warning('Login failed: User not found');
                return [
                    'success' => false,
                    'message' => 'Credenciais inválidas'
                ];
            }
            
            Log::info('Password comparison', [
                'provided_password' => $credentials['password'],
                'stored_hash' => substr($user->password, 0, 30) . '...',
                'hash_check' => Hash::check($credentials['password'], $user->password)
            ]);
            
            if (!Hash::check($credentials['password'], $user->password)) {
                Log::warning('Login failed: Password mismatch');
                return [
                    'success' => false,
                    'message' => 'Credenciais inválidas'
                ];
            }

            // Verifica se o usuário está ativo
            if (!$user->is_active) {
                Log::warning('Login failed: User inactive', ['user_id' => $user->id]);
                return [
                    'success' => false,
                    'message' => 'Usuário inativo'
                ];
            }

            Log::info('Login successful', ['user_id' => $user->id]);

            // Gera o token JWT
            $token = JWTAuth::fromUser($user);

            // Atualiza o último login
            $user->updateLastLogin();

            // Cache dos dados do usuário por 1 hora
            Cache::put("user_data_{$user->id}", $user, 3600);

            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Login realizado com sucesso'
            ];

        } catch (\Exception $e) {
            Log::error('Erro no serviço de login: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra um novo usuário
     */
    public function register(array $data): array
    {
        try {
            // Define como ativo por padrão
            $data['is_active'] = $data['is_active'] ?? true;

            // Cria o usuário
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'tenant_id' => $data['tenant_id'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            // Por enquanto, não atribui perfis automaticamente
            // TODO: Implementar sistema de perfis quando necessário

            // Carrega os relacionamentos
            $user->load(['tenant', 'profiles.permissions']);

            // Gera o token JWT
            $token = JWTAuth::fromUser($user);

            // Cache dos dados do usuário
            Cache::put("user_data_{$user->id}", $user, 3600);

            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Usuário registrado com sucesso'
            ];

        } catch (\Exception $e) {
            Log::error('Erro no serviço de registro: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envia link de recuperação de senha
     */
    public function sendPasswordResetLink(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }

            $status = Password::sendResetLink(['email' => $email]);

            if ($status === Password::RESET_LINK_SENT) {
                return [
                    'success' => true,
                    'message' => 'Link de recuperação enviado para seu email'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao enviar link de recuperação'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao enviar link de recuperação: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reseta a senha do usuário
     */
    public function resetPassword(array $data): array
    {
        try {
            $status = Password::reset(
                [
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'password_confirmation' => $data['password_confirmation'],
                    'token' => $data['token']
                ],
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));

                    // Remove o cache do usuário
                    Cache::forget("user_data_{$user->id}");
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return [
                    'success' => true,
                    'message' => 'Senha resetada com sucesso'
                ];
            }

            return [
                'success' => false,
                'message' => 'Token inválido ou expirado'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao resetar senha: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public function hasPermission(User $user, string $permission): bool
    {
        try {
            // Verifica no cache primeiro
            $cacheKey = "user_permissions_{$user->id}";
            $permissions = Cache::get($cacheKey);

            if (!$permissions) {
                $permissions = $user->profile?->permissions?->pluck('name')->toArray() ?? [];
                Cache::put($cacheKey, $permissions, 1800); // 30 minutos
            }

            return in_array($permission, $permissions);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar permissão: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalida o cache do usuário
     */
    public function invalidateUserCache(int $userId): void
    {
        Cache::forget("user_data_{$userId}");
        Cache::forget("user_permissions_{$userId}");
    }

    /**
     * Atualiza o perfil do usuário
     */
    public function updateProfile(User $user, array $data): array
    {
        try {
            $user->update($data);
            
            // Invalida o cache
            $this->invalidateUserCache($user->id);
            
            // Recarrega os relacionamentos
            $user->load(['tenant', 'profile.permissions']);
            
            // Atualiza o cache
            Cache::put("user_data_{$user->id}", $user, 3600);

            return [
                'success' => true,
                'user' => $user,
                'message' => 'Perfil atualizado com sucesso'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil: ' . $e->getMessage());
            throw $e;
        }
    }
}
