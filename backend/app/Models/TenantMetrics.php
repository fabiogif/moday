<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TenantMetrics extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'metric_date',
        'total_users',
        'active_users',
        'total_orders',
        'total_revenue',
        'messages_sent',
        'messages_whatsapp',
        'messages_email',
        'messages_sms',
        'login_count',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'total_users' => 'integer',
        'active_users' => 'integer',
        'total_orders' => 'integer',
        'total_revenue' => 'decimal:2',
        'messages_sent' => 'integer',
        'messages_whatsapp' => 'integer',
        'messages_email' => 'integer',
        'messages_sms' => 'integer',
        'login_count' => 'integer',
    ];

    // Scopes
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('metric_date', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->where('metric_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('metric_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('metric_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    // Relations
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Methods
    public static function recordDaily(Tenant $tenant): self
    {
        $today = today();

        // Busca ou cria métrica do dia
        $metric = self::firstOrNew([
            'tenant_id' => $tenant->id,
            'metric_date' => $today,
        ]);

        // Calcula métricas
        $metric->total_users = User::where('tenant_id', $tenant->id)->count();
        $metric->active_users = User::where('tenant_id', $tenant->id)
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();
        
        $metric->total_orders = Order::where('tenant_id', $tenant->id)
            ->whereDate('created_at', $today)
            ->count();
        
        $metric->total_revenue = Order::where('tenant_id', $tenant->id)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total_amount');
        
        // Contagem de logins do dia
        $metric->login_count = TenantAccessLog::where('tenant_id', $tenant->id)
            ->whereDate('logged_in_at', $today)
            ->count();

        // TODO: Implementar contagem de mensagens quando o sistema de mensagens estiver pronto
        // $metric->messages_sent = 0;
        // $metric->messages_whatsapp = 0;
        // $metric->messages_email = 0;
        // $metric->messages_sms = 0;

        $metric->save();

        return $metric;
    }

    public static function getAggregated(int $tenantId, $startDate, $endDate): array
    {
        $metrics = self::forTenant($tenantId)
            ->forPeriod($startDate, $endDate)
            ->get();

        return [
            'total_users' => $metrics->max('total_users'),
            'active_users' => $metrics->max('active_users'),
            'total_orders' => $metrics->sum('total_orders'),
            'total_revenue' => $metrics->sum('total_revenue'),
            'messages_sent' => $metrics->sum('messages_sent'),
            'messages_whatsapp' => $metrics->sum('messages_whatsapp'),
            'messages_email' => $metrics->sum('messages_email'),
            'messages_sms' => $metrics->sum('messages_sms'),
            'login_count' => $metrics->sum('login_count'),
            'daily_average_orders' => $metrics->avg('total_orders'),
            'daily_average_revenue' => $metrics->avg('total_revenue'),
        ];
    }

    public static function getTotals(): array
    {
        $today = today();
        
        return [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('account_status', 'active')->count(),
            'trial_tenants' => Tenant::where('account_status', 'trial')->count(),
            'expired_tenants' => Tenant::where('account_status', 'expired')->count(),
            'total_orders_today' => self::where('metric_date', $today)->sum('total_orders'),
            'total_revenue_today' => self::where('metric_date', $today)->sum('total_revenue'),
            'total_logins_today' => self::where('metric_date', $today)->sum('login_count'),
        ];
    }
}

