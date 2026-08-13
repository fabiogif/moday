<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AdminUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeSuperAdmins($query)
    {
        return $query->where('role', 'super_admin');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeAnalysts($query)
    {
        return $query->where('role', 'analyst');
    }

    // Relations
    public function actionLogs()
    {
        return $this->hasMany(AdminActionLog::class);
    }

    // Methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAnalyst(): bool
    {
        return $this->role === 'analyst';
    }

    public function canManageTenants(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function canManageAdmins(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canViewReports(): bool
    {
        return true; // Todos podem ver relatórios
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = [
            'super_admin' => ['*'],
            'admin' => [
                'tenants.view',
                'tenants.manage',
                'tenants.suspend',
                'tenants.activate',
                'metrics.view',
                'reports.view',
                'reports.generate',
            ],
            'analyst' => [
                'tenants.view',
                'metrics.view',
                'reports.view',
            ],
        ];

        $rolePermissions = $permissions[$this->role] ?? [];

        return in_array('*', $rolePermissions) || in_array($permission, $rolePermissions);
    }

    public function logAction(string $action, ?Tenant $tenant, string $description, ?array $oldValues = null, ?array $newValues = null): AdminActionLog
    {
        return AdminActionLog::create([
            'admin_user_id' => $this->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}

