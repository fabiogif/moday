<?php

namespace App\Services;

use App\Models\User;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\Plan;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}
    /**
     * Realiza o login do usuário
     */
    public function login(array $credentials): array
    {
        try {
            Log::info('AuthService login called', ['email' => $credentials['email']]);
            
            // Busca o usuário pelo email via repositório
            /** @var User|null $user */
            $user = $this->userRepository->getUserByEmail($credentials['email']);

            if ($user) {
                // Garantir que os relacionamentos necessários estejam carregados
                $user->loadMissing(['tenant', 'profile.permissions']);
            }

            Log::info('User search result', ['found' => $user ? true : false, 'user_id' => $user?->id]);

            // Verifica se o usuário existe e se a senha está correta
            if (!$user) {
                Log::warning('Login failed: User not found');
                return [
                    'success' => false,
                    'message' => 'Credenciais inválidas'
                ];
            }
            
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

            // Verifica se o tenant está bloqueado
            if ($user->tenant && $user->tenant->is_blocked) {
                Log::warning('Login failed: Tenant blocked', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant->id,
                    'blocked_reason' => $user->tenant->blocked_reason
                ]);
                
                $message = 'Acesso temporariamente bloqueado.';
                if ($user->tenant->blocked_reason) {
                    $message .= ' Motivo: ' . $user->tenant->blocked_reason;
                }
                
                return [
                    'success' => false,
                    'message' => $message
                ];
            }

            Log::info('Login successful', ['user_id' => $user->id]);

            // Verifica e ativa trial se necessário
            $trialStatus = $this->checkAndActivateTrial($user);

            // Gera o token JWT
            $token = JWTAuth::fromUser($user);

            // Atualiza o último login
            $user->updateLastLogin();

            // Carrega perfis para que o frontend possa checar permissões
            $user->loadMissing('profiles');

            // Cache dos dados do usuário pelo período do token
            Cache::put("user_data_{$user->id}", $user, config('jwt.ttl', 120) * 60);

            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'trial_status' => $trialStatus,
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
        DB::beginTransaction();

        try {
            // Define como ativo por padrão
            $data['is_active'] = $data['is_active'] ?? true;

            $tenant = null;

            if (empty($data['tenant_id'])) {
                $tenant = $this->createTenantForRegistration($data);
                $data['tenant_id'] = $tenant->id;
            } else {
                /** @var Tenant $tenant */
                $tenant = $this->tenantRepository->getById((int) $data['tenant_id']);
                if (!$tenant) {
                    throw new \RuntimeException('Tenant não encontrado para registro');
                }
            }

            // Cria o usuário via repositório (já faz hash da senha se necessário)
            /** @var User $user */
            $user = $this->userRepository->createUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'tenant_id' => $data['tenant_id'],
                'is_active' => $data['is_active'],
            ]);

            DB::commit();

            // Carrega os relacionamentos
            $user->load(['tenant', 'profiles.permissions']);

            // Gera o token JWT
            $token = JWTAuth::fromUser($user);

            // Cache dos dados do usuário
            Cache::put("user_data_{$user->id}", $user, config('jwt.ttl', 120) * 60);

            return [
                'success' => true,
                'user' => $user,
                'tenant' => $user->tenant,
                'token' => $token,
                'message' => 'Usuário registrado com sucesso'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
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
     * Verifica e ativa trial se necessário
     */
    private function checkAndActivateTrial(User $user): array
    {
        try {
            if (!$user->tenant_id) {
                return [
                    'is_trial' => false,
                    'is_active' => true,
                    'days_remaining' => 0,
                    'expires_at' => null,
                    'needs_payment' => false,
                ];
            }

            /** @var Tenant|null $tenant */
            $tenant = $this->tenantRepository->getById((int) $user->tenant_id);

            if (!$tenant) {
                return [
                    'is_trial' => false,
                    'is_active' => false,
                    'days_remaining' => 0,
                    'expires_at' => null,
                    'needs_payment' => true,
                ];
            }

            $tenant->loadMissing('plan');

            // Normaliza tenants legados no plano Grátis
            if ($tenant->isOnFreePlan() && $tenant->account_status === 'trial') {
                $planName = $tenant->plan?->name ?? $tenant->subscription_plan ?? 'Grátis';
                Log::info('Normalizando tenant Grátis legado para active', ['tenant_id' => $tenant->id]);
                $tenant->activateFreePlan($planName);
                $tenant->refresh();
            }

            // Fallback: inicia trial apenas para planos pagos sem data de início
            if (
                $tenant->plan?->requiresTrial()
                && !$tenant->trial_started_at
                && $tenant->account_status === 'trial'
            ) {
                Log::info('Ativando trial para tenant', ['tenant_id' => $tenant->id]);
                $tenant->startTrial();
                $tenant->refresh();
            }

            return $tenant->toTrialStatusArray();

        } catch (\Exception $e) {
            Log::error('Erro ao verificar trial: ' . $e->getMessage());
            return [
                'is_trial' => false,
                'is_active' => true,
                'days_remaining' => 0,
                'expires_at' => null,
                'needs_payment' => false,
            ];
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

    private function createTenantForRegistration(array $data): Tenant
    {
        $plan = Plan::query()->where('is_active', true)->orderBy('price')->first();

        if (!$plan) {
            $plan = Plan::factory()->create([
                'name' => 'Plano Padrão',
                'price' => 0,
                'description' => 'Plano gerado automaticamente para registro de usuário.',
                'is_active' => true,
            ]);
        }

        $slug = $this->generateUniqueTenantSlug($data['name']);

        return Tenant::create([
            'plan_id' => $plan->id,
            'uuid' => (string) Str::uuid(),
            'cnpj' => $this->generateCnpj(),
            'name' => $data['name'],
            'email' => $data['email'],
            'url' => $slug,
            'slug' => $slug,
            'active' => 'Y',
            'is_active' => true,
            'account_status' => 'trial',
            'trial_started_at' => now(),
            'trial_expires_at' => now()->addDays((int) config('subscription.trial_days', 7)),
        ]);
    }

    private function generateUniqueTenantSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        if (empty($baseSlug)) {
            $baseSlug = 'conta';
        }

        $slug = $baseSlug;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . Str::lower(Str::random(5));
        }

        return $slug;
    }

    private function generateCnpj(): string
    {
        return str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    }
}
