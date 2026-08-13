<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'logged_in_at',
        'logged_out_at',
        'session_duration',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'session_duration' => 'integer',
    ];

    // Scopes
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_in_at', [$startDate, $endDate]);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('logged_out_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('logged_out_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('logged_in_at', today());
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('logged_in_at', 'desc');
    }

    // Relations
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public function endSession(): void
    {
        $loggedOutAt = now();
        $duration = $this->logged_in_at->diffInMinutes($loggedOutAt);

        $this->update([
            'logged_out_at' => $loggedOutAt,
            'session_duration' => $duration,
        ]);
    }

    public function isActive(): bool
    {
        return is_null($this->logged_out_at);
    }

    public static function recordLogin(Tenant $tenant, User $user, $request = null): self
    {
        return self::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'logged_in_at' => now(),
            'ip_address' => $request?->ip() ?? request()->ip(),
            'user_agent' => $request?->userAgent() ?? request()->userAgent(),
        ]);
    }

    public static function getStats(int $tenantId, $startDate = null, $endDate = null): array
    {
        $query = self::forTenant($tenantId);

        if ($startDate && $endDate) {
            $query->forPeriod($startDate, $endDate);
        }

        $logs = $query->get();

        return [
            'total_logins' => $logs->count(),
            'unique_users' => $logs->unique('user_id')->count(),
            'average_session_duration' => $logs->whereNotNull('session_duration')->avg('session_duration'),
            'total_session_duration' => $logs->sum('session_duration'),
            'active_sessions' => $logs->where('logged_out_at', null)->count(),
        ];
    }

    public static function getRecentActivity(int $tenantId, int $limit = 10): \Illuminate\Support\Collection
    {
        return self::forTenant($tenantId)
            ->with('user:id,name,email')
            ->recent()
            ->limit($limit)
            ->get();
    }
}

