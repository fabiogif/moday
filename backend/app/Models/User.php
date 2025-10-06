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
        'is_active',
        'email_verified_at',
        'phone',
        'avatar',
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
     */
    public function hasProfile(string $profileName): bool
    {
        return $this->profiles()
            ->whereRaw('LOWER(name) = ?', [strtolower($profileName)])
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
     */
    public function hasPermissionTo(string $permission): bool
    {
        // Check direct permissions
        if ($this->permissions()->where('slug', $permission)->exists()) {
            return true;
        }

        // Check permissions through profiles
        return $this->profiles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Check direct permissions
        if ($this->permissions()->whereIn('slug', $permissions)->exists()) {
            return true;
        }

        // Check permissions through profiles
        return $this->profiles()
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('slug', $permissions);
            })
            ->exists();
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->getAllPermissions()->pluck('slug')->toArray();
        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    /**
     * Get all permissions for the user (direct + through profiles).
     */
    public function getAllPermissions()
    {
        $directPermissions = $this->permissions;
        $profilePermissions = $this->profiles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten();

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
        return $this->hasProfile('super-admin') || $this->hasProfile('super_admin');
    }

    /**
     * Check if user is admin (via profile).
     */
    public function isAdmin(): bool
    {
        return $this->hasProfile('admin') || $this->isSuperAdmin();
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
