<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id',
        'tenant_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Scopes
    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('admin_user_id', $adminId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Relations
    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Methods
    public static function log(
        AdminUser $admin,
        string $action,
        string $description,
        ?Tenant $tenant = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'admin_user_id' => $admin->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }

    public static function getRecentActivity(int $limit = 20): \Illuminate\Support\Collection
    {
        return self::with(['adminUser:id,name,email', 'tenant:id,name'])
            ->recent()
            ->limit($limit)
            ->get();
    }

    public static function getActivityForTenant(int $tenantId, int $limit = 50): \Illuminate\Support\Collection
    {
        return self::forTenant($tenantId)
            ->with('adminUser:id,name,email')
            ->recent()
            ->limit($limit)
            ->get();
    }

    public static function getStatsForPeriod($startDate, $endDate): array
    {
        $logs = self::forPeriod($startDate, $endDate)->get();

        return [
            'total_actions' => $logs->count(),
            'unique_admins' => $logs->unique('admin_user_id')->count(),
            'actions_by_type' => $logs->groupBy('action')->map->count(),
            'most_active_admin' => $logs->groupBy('admin_user_id')
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first(),
        ];
    }

    public function getChanges(): array
    {
        if (empty($this->old_values) || empty($this->new_values)) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}

