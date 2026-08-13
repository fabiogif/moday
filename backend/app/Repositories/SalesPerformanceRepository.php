<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Client;
use App\Repositories\Contracts\SalesPerformanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesPerformanceRepository implements SalesPerformanceRepositoryInterface
{
    public function __construct(
        protected Order $orderModel,
        protected Client $clientModel
    ) {}

    public function getTotalSales(int $tenantId, string $startDate, string $endDate): int
    {
        return $this->orderModel
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'Cancelado')
            ->count();
    }

    public function getTotalSalesValue(int $tenantId, string $startDate, string $endDate): float
    {
        return (float) $this->orderModel
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'Cancelado')
            ->sum('total');
    }

    public function getAverageTicket(int $tenantId, string $startDate, string $endDate): float
    {
        $totalSales = $this->getTotalSales($tenantId, $startDate, $endDate);
        if ($totalSales === 0) {
            return 0.0;
        }

        $totalValue = $this->getTotalSalesValue($tenantId, $startDate, $endDate);
        return (float) ($totalValue / $totalSales);
    }

    public function getNewClientsCount(int $tenantId, string $startDate, string $endDate): int
    {
        return $this->clientModel
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    public function getSalesByHour(int $tenantId, string $startDate, string $endDate): array
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        switch ($connection) {
            case 'pgsql':
                $selectRaw = "EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as count";
                $groupBy = "EXTRACT(HOUR FROM created_at)";
                break;
            case 'sqlite':
                $selectRaw = "CAST(strftime('%H', created_at) AS INTEGER) as hour, COUNT(*) as count";
                $groupBy = "CAST(strftime('%H', created_at) AS INTEGER)";
                break;
            default:
                $selectRaw = "HOUR(created_at) as hour, COUNT(*) as count";
                $groupBy = "HOUR(created_at)";
        }

        $salesByHour = $this->orderModel
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'Cancelado')
            ->selectRaw($selectRaw)
            ->groupBy(DB::raw($groupBy))
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => (int) $item->hour,
                    'count' => (int) $item->count,
                    'hour_label' => sprintf('%02d:00', (int) $item->hour)
                ];
            })
            ->toArray();

        // Fill missing hours with 0
        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $found = false;
            foreach ($salesByHour as $item) {
                if ($item['hour'] === $hour) {
                    $result[] = $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $result[] = [
                    'hour' => $hour,
                    'count' => 0,
                    'hour_label' => sprintf('%02d:00', $hour)
                ];
            }
        }

        return $result;
    }

    public function getSalesByDayOfWeek(int $tenantId, string $startDate, string $endDate): array
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        $dayNames = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];

        switch ($connection) {
            case 'pgsql':
                $expression = "EXTRACT(DOW FROM created_at)";
                break;
            case 'sqlite':
                $expression = "CAST(strftime('%w', created_at) AS INTEGER)";
                break;
            default:
                $expression = "DAYOFWEEK(created_at) - 1";
        }

        $selectRaw = "{$expression} as day_of_week, COUNT(*) as count";
        $groupBy = $expression;

        $salesByDay = $this->orderModel
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'Cancelado')
            ->selectRaw($selectRaw)
            ->groupBy(DB::raw($groupBy))
            ->orderBy('day_of_week')
            ->get()
            ->map(function ($item) use ($dayNames) {
                $dayIndex = (int) $item->day_of_week;
                // PostgreSQL DOW returns 0-6 (0=Sunday), MySQL DAYOFWEEK returns 1-7 (1=Sunday)
                // After -1 in MySQL, we get 0-6 (0=Sunday)
                if ($dayIndex < 0 || $dayIndex > 6) {
                    $dayIndex = 0;
                }
                return [
                    'day_of_week' => $dayIndex,
                    'day_name' => $dayNames[$dayIndex],
                    'count' => (int) $item->count
                ];
            })
            ->toArray();

        // Fill missing days with 0
        $result = [];
        for ($day = 0; $day < 7; $day++) {
            $found = false;
            foreach ($salesByDay as $item) {
                if ($item['day_of_week'] === $day) {
                    $result[] = $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $result[] = [
                    'day_of_week' => $day,
                    'day_name' => $dayNames[$day],
                    'count' => 0
                ];
            }
        }

        return $result;
    }

    public function getSalesByPaymentMethod(int $tenantId, string $startDate, string $endDate): array
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'pgsql') {
            $paymentMethodColumn = "COALESCE(payment_methods.name, orders.payment_method, 'Não informado')";
        } else {
            $paymentMethodColumn = "COALESCE(payment_methods.name, orders.payment_method, 'Não informado')";
        }

        $salesByPaymentMethod = DB::table('orders')
            ->leftJoin('payment_methods', function ($join) {
                $join->on('orders.payment_method_id', '=', 'payment_methods.uuid')
                     ->where('payment_methods.tenant_id', '=', DB::raw('orders.tenant_id'));
            })
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', 'Cancelado')
            ->select(
                DB::raw("{$paymentMethodColumn} as payment_method_name"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(orders.total) as total_value')
            )
            ->groupBy('payment_method_name')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'payment_method' => $item->payment_method_name ?? 'Não informado',
                    'count' => (int) $item->count,
                    'total_value' => (float) $item->total_value
                ];
            })
            ->toArray();

        return $salesByPaymentMethod;
    }

    public function getPreviousPeriodData(int $tenantId, string $startDate, string $endDate, int $days): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $duration = $start->diffInDays($end);

        $previousStart = $start->copy()->subDays($duration + 1);
        $previousEnd = $start->copy()->subDay();

        return [
            'start_date' => $previousStart->format('Y-m-d H:i:s'),
            'end_date' => $previousEnd->format('Y-m-d H:i:s'),
            'total_sales' => $this->getTotalSales($tenantId, $previousStart->format('Y-m-d H:i:s'), $previousEnd->format('Y-m-d H:i:s')),
            'total_sales_value' => $this->getTotalSalesValue($tenantId, $previousStart->format('Y-m-d H:i:s'), $previousEnd->format('Y-m-d H:i:s')),
            'average_ticket' => $this->getAverageTicket($tenantId, $previousStart->format('Y-m-d H:i:s'), $previousEnd->format('Y-m-d H:i:s')),
            'new_clients' => $this->getNewClientsCount($tenantId, $previousStart->format('Y-m-d H:i:s'), $previousEnd->format('Y-m-d H:i:s'))
        ];
    }
}

