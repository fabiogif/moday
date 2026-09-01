<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

readonly class AdminAuthService
{
    public function __construct(
        private AdminUserRepositoryInterface $adminUserRepository
    ) {}

    public function attemptLogin(string $email, string $password, string $ip): array
    {
        Log::info('AdminAuthService login attempt', [
            'email' => $email,
            'ip' => $ip,
            'request_id' => uniqid('admin_auth_'),
        ]);

        $admin = $this->adminUserRepository->findByEmail($email);

        if (!$admin || !Hash::check($password, $admin->password)) {
            Log::warning('AdminAuthService login failed - invalid credentials', [
                'email' => $email,
                'ip' => $ip,
                'request_id' => uniqid('admin_auth_'),
            ]);

            return ['success' => false, 'reason' => 'invalid_credentials'];
        }

        if (!$admin->is_active) {
            Log::warning('AdminAuthService login failed - inactive account', [
                'email' => $email,
                'admin_id' => $admin->id,
                'ip' => $ip,
                'request_id' => uniqid('admin_auth_'),
            ]);

            return ['success' => false, 'reason' => 'inactive'];
        }

        $admin->updateLastLogin();
        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        Log::info('AdminAuthService login successful', [
            'admin_id' => $admin->id,
            'email' => $admin->email,
            'role' => $admin->role,
            'ip' => $ip,
            'request_id' => uniqid('admin_auth_'),
        ]);

        return [
            'success' => true,
            'admin' => $this->formatAdmin($admin),
            'token' => $token,
        ];
    }

    public function logout(AdminUser $admin): void
    {
        $token = $admin->currentAccessToken();
        if ($token) {
            $token->delete();
        }
    }

    public function refreshToken(AdminUser $admin): string
    {
        $token = $admin->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $admin->createToken('admin-token', ['admin'])->plainTextToken;
    }

    public function getMeData(AdminUser $admin): array
    {
        return array_merge($this->formatAdmin($admin), [
            'permissions' => $this->getPermissionsForRole($admin->role),
        ]);
    }

    private function formatAdmin(AdminUser $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'is_active' => $admin->is_active,
            'last_login_at' => $admin->last_login_at,
        ];
    }

    public function sendPasswordResetLink(string $email): array
    {
        $status = Password::broker('admin_users')->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            Log::info('Admin password reset link não enviado', ['email' => $email, 'status' => $status]);
        }

        return [
            'success' => true,
            'message' => 'Se este e-mail estiver cadastrado, enviamos um link de recuperação.',
        ];
    }

    public function resetPassword(array $data): array
    {
        $status = Password::broker('admin_users')->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'token' => $data['token'],
            ],
            function (AdminUser $admin, string $password) {
                $admin->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $admin->save();

                event(new PasswordReset($admin));

                Cache::forget("admin_data_{$admin->id}");
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ['success' => true, 'message' => 'Senha redefinida com sucesso'];
        }

        return ['success' => false, 'message' => 'Token inválido ou expirado'];
    }

    public function getPermissionsForRole(string $role): array
    {
        $permissions = [
            'super_admin' => [
                'tenants' => ['view', 'create', 'update', 'delete', 'activate', 'suspend', 'block'],
                'admins' => ['view', 'create', 'update', 'delete'],
                'metrics' => ['view', 'export'],
                'reports' => ['view', 'generate', 'export', 'schedule'],
                'billing' => ['view', 'create', 'update', 'mark_paid'],
                'logs' => ['view', 'export'],
            ],
            'admin' => [
                'tenants' => ['view', 'update', 'activate', 'suspend'],
                'metrics' => ['view', 'export'],
                'reports' => ['view', 'generate', 'export'],
                'billing' => ['view'],
                'logs' => ['view'],
            ],
            'analyst' => [
                'tenants' => ['view'],
                'metrics' => ['view'],
                'reports' => ['view', 'export'],
            ],
        ];

        return $permissions[$role] ?? [];
    }
}
