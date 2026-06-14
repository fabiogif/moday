<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait UserACLTrait
{
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public function hasPermission(string $permissionName): bool
    {
        // 1. Verificação de Admin
        if ($this->isAdmin()) {
            return true;
        }

        // 2. Logging para debug
        Log::debug('Verificando permissão', [
            'user_id' => $this->id,
            'permission' => $permissionName
        ]);

        // 3. Método de verificação configurável
        $checkMethod = config('acl.check_method', 'both');

        switch ($checkMethod) {
            case 'roles':
                return $this->hasPermissionThroughRoles($permissionName);
            case 'permissions':
                return in_array($permissionName, $this->getPermissionsList());
            default: // 'both'
                return in_array($permissionName, $this->getPermissionsList()) ||
                       $this->hasPermissionThroughRoles($permissionName);
        }
    }

    /**
     * Obtém lista de permissões do usuário
     */
    public function getPermissionsList(): array
    {
        if ($this->isAdmin()) {
            return $this->getAllPermissionsFromDatabase();
        }

        // Cache com TTL configurável
        if (config('acl.cache.enabled', false)) {
            $cacheKey = "user_permissions_{$this->id}";
            $cacheTtl = config('acl.cache.ttl', 60 * 24); // 24 horas

            return Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->permissionsRole();
            });
        }

        return $this->permissionsRole();
    }

    /**
     * Verifica permissão através de roles
     */
    protected function hasPermissionThroughRoles(string $permissionSlug): bool
    {
        return $this->roles()->whereHas('permissions', function ($q) use ($permissionSlug) {
            $q->where('slug', $permissionSlug);
        })->exists();
    }

    /**
     * Obtém permissões através de roles e profiles
     */
    protected function permissionsRole(): array
    {
        // Permissões através de roles
        $rolePermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->toArray();

        // Permissões através de profiles
        // Permissões através de profile (tanto 1:N quanto N:N)
        $profilePermissions = collect();

        if (method_exists($this, 'profile') && $this->relationLoaded('profile') ? $this->profile : $this->profile()->exists()) {
            $profilePermissions = $profilePermissions->merge(
                optional($this->profile()->with('permissions')->first())->permissions?->pluck('slug') ?? collect()
            );
        }

        if (method_exists($this, 'profiles')) {
            $profilePermissions = $profilePermissions->merge(
                $this->profiles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->pluck('slug')
            );
        }

        $profilePermissions = $profilePermissions->unique()->values()->toArray();

        // Permissões diretas
        $directPermissions = $this->permissions()
            ->pluck('slug')
            ->toArray();

        // Merge e remove duplicatas
        return array_unique(array_merge($rolePermissions, $profilePermissions, $directPermissions));
    }

    /**
     * Obtém todas as permissões do banco de dados (para admin)
     */
    protected function getAllPermissionsFromDatabase(): array
    {
        return \App\Models\Permission::where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->pluck('slug')
            ->toArray();
    }

    /**
     * Verifica se o usuário é admin
     */
    public function isAdmin(): bool
    {
        $adminEmails = config('acl.admin_emails', []);
        return in_array($this->email, $adminEmails);
    }

    /**
     * Verifica se o usuário tem uma role específica
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Verifica se o usuário tem qualquer uma das roles fornecidas
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Verifica se o usuário tem todas as roles fornecidas
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->roles()->pluck('name')->toArray();
        return count(array_intersect($roles, $userRoles)) === count($roles);
    }

    /**
     * Verifica se o usuário tem qualquer uma das permissões fornecidas
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $userPermissions = $this->getPermissionsList();
        return !empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Verifica se o usuário tem todas as permissões fornecidas
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->getPermissionsList();
        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    /**
     * Limpa o cache de permissões do usuário
     */
    public function clearPermissionsCache(): void
    {
        Cache::forget("user_permissions_{$this->id}");
    }

    /**
     * Obtém todas as permissões do usuário (objetos completos)
     */
    public function getAllPermissions()
    {
        $rolePermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten();

        $profilePermissions = $this->profiles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten();

        $directPermissions = $this->permissions;

        return $rolePermissions->merge($profilePermissions)->merge($directPermissions)->unique('id');
    }

    /**
     * Verifica se o usuário pode gerenciar outros usuários
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin() || $this->hasPermission('users.manage');
    }

    /**
     * Verifica se o usuário pode acessar o painel administrativo
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() || $this->hasPermission('admin.access');
    }

    /**
     * Verifica se o usuário é super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin') || $this->hasRole('super_admin');
    }

    /**
     * Verifica se o usuário é manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager') || $this->isAdmin();
    }
}
