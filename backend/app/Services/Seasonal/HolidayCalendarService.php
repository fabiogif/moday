<?php

namespace App\Services\Seasonal;

use App\Models\HolidayCalendar;
use App\Models\SeasonalAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayCalendarService
{
    private const DEFAULT_LOOKAHEAD_DAYS = 60;

    /**
     * Returns upcoming holidays for a tenant in the next $days days.
     * Includes system-wide (tenant_id = null) and tenant-specific holidays.
     */
    public function upcoming(int $tenantId, int $days = self::DEFAULT_LOOKAHEAD_DAYS): Collection
    {
        $today = Carbon::today();
        $until = $today->copy()->addDays($days);

        return HolidayCalendar::active()
            ->forTenant($tenantId)
            ->get()
            ->map(function (HolidayCalendar $holiday) use ($today, $until) {
                $next = $holiday->nextOccurrence($today);
                if (!$next || $next->gt($until)) return null;

                return [
                    'id'               => $holiday->id,
                    'name'             => $holiday->name,
                    'slug'             => $holiday->slug,
                    'type'             => $holiday->type,
                    'category'         => $holiday->category,
                    'date'             => $next->toDateString(),
                    'days_until'       => (int) $today->diffInDays($next),
                    'alert_days_before'=> $holiday->alert_days_before,
                    'notes'            => $holiday->notes,
                ];
            })
            ->filter()
            ->sortBy('days_until')
            ->values();
    }

    /**
     * Generates SeasonalAlert records for holidays that fall within their alert window.
     * Skips holidays that already have a non-acknowledged alert this year.
     */
    public function generateAlerts(int $tenantId): int
    {
        $today    = Carbon::today();
        $created  = 0;

        HolidayCalendar::active()->forTenant($tenantId)->get()
            ->each(function (HolidayCalendar $holiday) use ($tenantId, $today, &$created) {
                $next = $holiday->nextOccurrence($today);
                if (!$next) return;

                $daysUntil = (int) $today->diffInDays($next);
                if ($daysUntil > $holiday->alert_days_before) return;

                // Avoid duplicate alerts in the same year
                $alreadyAlerted = SeasonalAlert::forTenant($tenantId)
                    ->where('type', 'holiday_upcoming')
                    ->whereDate('reference_date', $next->toDateString())
                    ->whereJsonContains('metadata->holiday_id', $holiday->id)
                    ->exists();

                if ($alreadyAlerted) return;

                SeasonalAlert::create([
                    'tenant_id'      => $tenantId,
                    'type'           => 'holiday_upcoming',
                    'priority'       => $daysUntil <= 7 ? 'high' : 'normal',
                    'title'          => "{$holiday->name} em {$daysUntil} dia(s)",
                    'body'           => "Prepare seu estoque e promoções para {$holiday->name} em {$next->format('d/m/Y')}.",
                    'metadata'       => [
                        'holiday_id'  => $holiday->id,
                        'holiday_slug'=> $holiday->slug,
                        'days_until'  => $daysUntil,
                    ],
                    'reference_date' => $next->toDateString(),
                ]);

                $created++;
            });

        return $created;
    }

    public function list(int $tenantId): Collection
    {
        return HolidayCalendar::active()->forTenant($tenantId)->orderBy('month')->orderBy('day')->get();
    }

    public function create(int $tenantId, array $data): HolidayCalendar
    {
        return HolidayCalendar::create(array_merge($data, ['tenant_id' => $tenantId]));
    }

    public function update(int $tenantId, int $id, array $data): HolidayCalendar
    {
        $holiday = HolidayCalendar::where('tenant_id', $tenantId)->findOrFail($id);
        $holiday->update($data);
        return $holiday->fresh();
    }

    public function delete(int $tenantId, int $id): void
    {
        HolidayCalendar::where('tenant_id', $tenantId)->findOrFail($id)->delete();
    }
}
