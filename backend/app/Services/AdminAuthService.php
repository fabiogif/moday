<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
