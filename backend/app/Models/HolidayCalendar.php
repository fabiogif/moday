<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class HolidayCalendar extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'slug', 'month', 'day', 'specific_date',
        'type', 'category', 'alert_days_before', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'specific_date' => 'date',
            'is_active'     => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        });
    }

    /** Returns the next occurrence date from today. */
    public function nextOccurrence(?Carbon $from = null): ?Carbon
    {
        $from ??= Carbon::today();

        // Specific (floating) date
        if ($this->specific_date) {
            return $this->specific_date->greaterThanOrEqualTo($from) ? $this->specific_date->copy() : null;
        }

        // Fixed annual: month + day
        if ($this->month && $this->day) {
            $candidate = Carbon::createFromDate($from->year, $this->month, $this->day);
            if ($candidate->lt($from)) {
                $candidate->addYear();
            }
            return $candidate;
        }

        return null;
    }

    /** How many days until next occurrence. */
    public function daysUntilNext(): ?int
    {
        $next = $this->nextOccurrence();
        return $next ? (int) Carbon::today()->diffInDays($next) : null;
    }
}
