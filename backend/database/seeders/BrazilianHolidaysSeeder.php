<?php

namespace Database\Seeders;

use App\Models\HolidayCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BrazilianHolidaysSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        // Fixed annual dates (month + day, apply every year)
        $fixed = [
            ['slug' => 'ano-novo',              'name' => 'Ano Novo',                     'month' => 1,  'day' => 1,  'type' => 'national',       'category' => 'civil',      'alert_days_before' => 30],
            ['slug' => 'dia-das-maes',          'name' => 'Dia das Mães',                 'month' => 5,  'day' => 11, 'type' => 'commemorative',  'category' => 'comercial',  'alert_days_before' => 45],
            ['slug' => 'dia-dos-namorados',     'name' => 'Dia dos Namorados',            'month' => 6,  'day' => 12, 'type' => 'commemorative',  'category' => 'comercial',  'alert_days_before' => 30],
            ['slug' => 'dia-dos-pais',          'name' => 'Dia dos Pais',                 'month' => 8,  'day' => 11, 'type' => 'commemorative',  'category' => 'comercial',  'alert_days_before' => 45],
            ['slug' => 'dia-das-criancas',      'name' => 'Dia das Crianças',             'month' => 10, 'day' => 12, 'type' => 'commemorative',  'category' => 'comercial',  'alert_days_before' => 30],
            ['slug' => 'halloween',             'name' => 'Halloween',                    'month' => 10, 'day' => 31, 'type' => 'commemorative',  'category' => 'comercial',  'alert_days_before' => 20],
            ['slug' => 'finados',               'name' => 'Finados',                      'month' => 11, 'day' => 2,  'type' => 'national',       'category' => 'civil',      'alert_days_before' => 15],
            ['slug' => 'black-friday',          'name' => 'Black Friday',                 'month' => 11, 'day' => 28, 'type' => 'commemorative',  'category' => 'comercial',  'alert_days_before' => 45],
            ['slug' => 'natal',                 'name' => 'Natal',                        'month' => 12, 'day' => 25, 'type' => 'national',       'category' => 'comercial',  'alert_days_before' => 60],
            ['slug' => 'reveillon',             'name' => 'Réveillon',                    'month' => 12, 'day' => 31, 'type' => 'commemorative',  'category' => 'comercial',  'alert_days_before' => 20],
            ['slug' => 'tiradentes',            'name' => 'Tiradentes',                   'month' => 4,  'day' => 21, 'type' => 'national',       'category' => 'civil',      'alert_days_before' => 10],
            ['slug' => 'independencia',         'name' => 'Independência do Brasil',      'month' => 9,  'day' => 7,  'type' => 'national',       'category' => 'civil',      'alert_days_before' => 10],
            ['slug' => 'nossa-senhora-aparecida','name'=> 'N. Sra. Aparecida',            'month' => 10, 'day' => 12, 'type' => 'national',       'category' => 'religioso',  'alert_days_before' => 10],
            ['slug' => 'proclamacao-republica', 'name' => 'Proclamação da República',     'month' => 11, 'day' => 15, 'type' => 'national',       'category' => 'civil',      'alert_days_before' => 10],
        ];

        foreach ($fixed as $data) {
            HolidayCalendar::updateOrCreate(
                ['slug' => $data['slug'], 'tenant_id' => null],
                array_merge($data, ['is_active' => true, 'tenant_id' => null]),
            );
        }

        // Floating dates for current year and next (Páscoa, Carnaval)
        $floatingDates = $this->computeFloatingDates($year);
        $floatingDates = array_merge($floatingDates, $this->computeFloatingDates($year + 1));

        foreach ($floatingDates as $data) {
            HolidayCalendar::updateOrCreate(
                ['slug' => $data['slug'], 'specific_date' => $data['specific_date'], 'tenant_id' => null],
                array_merge($data, ['is_active' => true, 'tenant_id' => null]),
            );
        }
    }

    private function computeFloatingDates(int $year): array
    {
        // Easter (Páscoa) — Gauss algorithm
        $easter = $this->computeEaster($year);

        return [
            [
                'slug'          => "carnaval-{$year}",
                'name'          => "Carnaval {$year}",
                'specific_date' => $easter->copy()->subDays(47)->toDateString(),
                'month'         => null, 'day' => null,
                'type'          => 'national', 'category' => 'civil',
                'alert_days_before' => 45,
            ],
            [
                'slug'          => "pascoa-{$year}",
                'name'          => "Páscoa {$year}",
                'specific_date' => $easter->toDateString(),
                'month'         => null, 'day' => null,
                'type'          => 'national', 'category' => 'religioso',
                'alert_days_before' => 45,
            ],
            [
                'slug'          => "corpus-christi-{$year}",
                'name'          => "Corpus Christi {$year}",
                'specific_date' => $easter->copy()->addDays(60)->toDateString(),
                'month'         => null, 'day' => null,
                'type'          => 'national', 'category' => 'religioso',
                'alert_days_before' => 10,
            ],
        ];
    }

    private function computeEaster(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::createFromDate($year, $month, $day);
    }
}
