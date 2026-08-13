<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Traits\UserACLTrait;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes, UserACLTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'profile_id',
        'is_active',
        'email_verified_at',
        'phone',
        'avatar',
        'rg',
        'job_position_id',
        'last_login_at',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'tenant_id' => $this->tenant_id,
            'profile_id' => $this->profile_id,
            'is_active' => $this->is_active,
        ];
    }

    /**
     * Relacionamento com tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com profile (many-to-many)
     */
    public function profiles()
    {
        return $this->belongsToMany(Profile::class, 'user_profiles');
    }

    /**
     * Relacionamento com profile (one-to-many inverse)
     * Preferido quando existir a coluna users.profile_id
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Relacionamento com roles (many-to-many)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Scope para usuários ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para usuários de um tenant específico
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Verifica se o usuário tem uma permissão específica
     * Este método é implementado pelo UserACLTrait
     */

    /**
     * Atualiza o último login
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }


    /**
     * Get the permissions for the user (direct and through profiles).
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * Check if user has a specific profile.
     * Uses case-insensitive comparison on profile name.
     * Considers both users.profile_id and the user_profiles pivot.
     */
    public function hasProfile(string $profileName): bool
    {
        $needle = strtolower($profileName);

        if ($this->profile()->whereRaw('LOWER(name) = ?', [$needle])->exists()) {
            return true;
        }

        return $this->profiles()
            ->whereRaw('LOWER(name) = ?', [$needle])
            ->exists();
    }

    /**
     * Check if user has any of the given profiles.
     */
    public function hasAnyProfile(array $profiles): bool
    {
        $profiles = array_map('strtolower', $profiles);
        return $this->profiles()
            ->whereRaw('LOWER(name) IN (' . implode(',', array_fill(0, count($profiles), '?')) . ')', $profiles)
            ->exists();
    }

    /**
     * Check if user has all of the given profiles.
     */
    public function hasAllProfiles(array $profiles): bool
    {
        $profiles = array_map('strtolower', $profiles);
        $userProfiles = $this->profiles()
            ->get()
            ->map(function ($profile) {
                return strtolower($profile->name);
            })
            ->toArray();
        return count(array_intersect($profiles, $userProfiles)) === count($profiles);
    }

    /**
     * Check if user has a specific role.
     * @deprecated Use hasProfile() instead
     */
    public function hasRole(string $role): bool
    {
        // Fallback: try to find as profile
        return $this->hasProfile($role);
    }

    /**
     * Check if user has any of the given roles.
     * @deprecated Use hasAnyProfile() instead
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->hasAnyProfile($roles);
    }

    /**
     * Check if user has all of the given roles.
     * @deprecated Use hasAllProfiles() instead
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->hasAllProfiles($roles);
    }

    /**
     * Check if user has a specific permission (direct or through profiles).
     * Administrador (e equivalentes) tem acesso a todos os recursos.
     */
    public function hasPermissionTo(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check direct permissions
        if ($this->permissions()->where('slug', $permission)->exists()) {
            return true;
        }

        // Check permissions through profiles (pivot)
        if ($this->profiles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists()) {
            return true;
        }

        // Check permissions through users.profile_id
        $directProfile = $this->profile;
        if ($directProfile && $directProfile->permissions()->where('slug', $permission)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check direct permissions
        if ($this->permissions()->whereIn('slug', $permissions)->exists()) {
            return true;
        }

        // Check permissions through profiles (pivot)
        if ($this->profiles()
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('slug', $permissions);
            })
            ->exists()) {
            return true;
        }

        $directProfile = $this->profile;
        if ($directProfile && $directProfile->permissions()->whereIn('slug', $permissions)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $userPermissions = $this->getAllPermissions()->pluck('slug')->toArray();
        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    /**
     * Get all permissions for the user (direct + through profiles).
     */
    public function getAllPermissions()
    {
        if ($this->isAdmin()) {
            return Permission::query()
                ->where('tenant_id', $this->tenant_id)
                ->where('is_active', true)
                ->get();
        }

        $directPermissions = $this->permissions;
        $profilePermissions = $this->profiles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten();

        if ($this->profile) {
            $profilePermissions = $profilePermissions->merge($this->profile->permissions);
        }

        return $directPermissions->merge($profilePermissions)->unique('id');
    }

    /**
     * Assign a profile to the user.
     */
    public function assignProfile(Profile $profile): void
    {
        if (!$this->hasProfile($profile->name)) {
            $this->profiles()->attach($profile);
        }
    }

    /**
     * Assign a role to the user.
     * @deprecated Use assignProfile() instead
     */
    public function assignRole(Role $role): void
    {
        // This method is deprecated
        // Use assignProfile() instead
    }

    /**
     * Remove a profile from the user.
     */
    public function removeProfile(Profile $profile): void
    {
        $this->profiles()->detach($profile);
    }

    /**
     * Sync profiles for the user.
     */
    public function syncProfiles(array $profiles): void
    {
        $this->profiles()->sync($profiles);
    }

    /**
     * Remove a role from the user.
     * @deprecated Use removeProfile() instead
     */
    public function removeRole(Role $role): void
    {
        // This method is deprecated
        // Use removeProfile() instead
    }

    /**
     * Sync roles for the user.
     * @deprecated Use syncProfiles() instead
     */
    public function syncRoles(array $roles): void
    {
        // This method is deprecated
        // Use syncProfiles() instead
    }

    /**
     * Give permission directly to the user.
     */
    public function givePermissionTo(Permission $permission): void
    {
        if (!$this->permissions()->where('id', $permission->id)->exists()) {
            $this->permissions()->attach($permission);
        }
    }

    /**
     * Revoke permission directly from the user.
     */
    public function revokePermissionTo(Permission $permission): void
    {
        $this->permissions()->detach($permission);
    }

    /**
     * Check if user is super admin (via profile).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasProfile('super-admin')
            || $this->hasProfile('super_admin')
            || $this->hasProfile('Super Admin');
    }

    /**
     * Check if user is admin (acesso total a páginas e recursos).
     * Perfil padrão do tenant: "Administrador".
     */
    public function isAdmin(): bool
    {
        $adminEmails = config('acl.admin_emails', []);
        if ($this->email && in_array($this->email, $adminEmails, true)) {
            return true;
        }

        return $this->hasProfile(\App\Services\TenantAclProvisioner::ADMIN_PROFILE_NAME)
            || $this->hasProfile('admin')
            || $this->isSuperAdmin();
    }

    /**
     * Check if user is manager (via profile).
     */
    public function isManager(): bool
    {
        return $this->hasProfile('manager') || $this->isAdmin();
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin() || $this->hasPermissionTo('users.manage');
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() || $this->hasPermissionTo('admin.access');
    }
}
