<?php

namespace App\Services;

use App\Models\SaleOrder;
use Illuminate\Support\Facades\DB;

class DistribtecReportService
{
    public function salesByPeriod(array $filters): array
    {
        $orders = $this->baseSaleOrderQuery($filters)
            ->with('client:id,company_name,trade_name')
            ->orderBy('ordered_at')
            ->get();

        $rows = $orders->map(fn ($order) => [
            'identify' => $order->identify,
            'client' => $order->client
                ? ($order->client->trade_name ?: $order->client->company_name)
                : 'Sem cliente',
            'ordered_at' => $order->ordered_at?->format('d/m/Y H:i'),
            'status' => SaleOrder::STATUSES[$order->status] ?? $order->status,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
        ]);

        $totalValue = $orders->sum('total');

        return [
            'summary' => [
                'orders_count' => $orders->count(),
                'total_value' => round($totalValue, 2),
                'avg_ticket' => $orders->count() > 0
                    ? round($totalValue / $orders->count(), 2)
                    : 0,
            ],
            'rows' => $rows->values()->all(),
        ];
    }

    public function salesByClient(array $filters): array
    {
        $rows = $this->baseSaleOrderQuery($filters)
            ->select(
                'client_id',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as total_value'),
                DB::raw('AVG(total) as avg_ticket')
            )
            ->whereNotNull('client_id')
            ->groupBy('client_id')
            ->orderByDesc('total_value')
            ->get();

        $clientIds = $rows->pluck('client_id')->filter()->all();
        $clients = DB::table('clients')
            ->whereIn('id', $clientIds)
            ->pluck('trade_name', 'id');

        $companyNames = DB::table('clients')
            ->whereIn('id', $clientIds)
            ->pluck('company_name', 'id');

        $mapped = $rows->map(function ($row) use ($clients, $companyNames) {
            $name = $clients[$row->client_id] ?? $companyNames[$row->client_id] ?? 'Cliente #' . $row->client_id;

            return [
                'client' => $name,
                'orders_count' => (int) $row->orders_count,
                'total_value' => round((float) $row->total_value, 2),
                'avg_ticket' => round((float) $row->avg_ticket, 2),
            ];
        });

        return [
            'summary' => [
                'clients_count' => $mapped->count(),
                'total_value' => round($mapped->sum('total_value'), 2),
            ],
            'rows' => $mapped->values()->all(),
        ];
    }

    public function abcProducts(array $filters): array
    {
        $query = DB::table('sale_order_items')
            ->join('sale_orders', 'sale_order_items.sale_order_id', '=', 'sale_orders.id')
            ->join('products', 'sale_order_items.product_id', '=', 'products.id')
            ->where('sale_orders.tenant_id', $filters['tenant_id'])
            ->where('sale_orders.status', '!=', 'cancelado')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_order_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_order_items.subtotal) as total_value')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_value');

        if (!empty($filters['start_date'])) {
            $query->whereDate('sale_orders.ordered_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('sale_orders.ordered_at', '<=', $filters['end_date']);
        }

        $products = $query->get();
        $grandTotal = $products->sum('total_value');
        $cumulative = 0;

        $rows = $products->map(function ($product) use ($grandTotal, &$cumulative) {
            $value = (float) $product->total_value;
            $participation = $grandTotal > 0 ? ($value / $grandTotal) * 100 : 0;
            $cumulative += $participation;

            $class = $cumulative <= 80 ? 'A' : ($cumulative <= 95 ? 'B' : 'C');

            return [
                'product' => $product->name,
                'total_quantity' => round((float) $product->total_quantity, 3),
                'total_value' => round($value, 2),
                'participation_percent' => round($participation, 2),
                'cumulative_percent' => round($cumulative, 2),
                'abc_class' => $class,
            ];
        });

        return [
            'summary' => [
                'products_count' => $rows->count(),
                'total_value' => round($grandTotal, 2),
            ],
            'rows' => $rows->values()->all(),
        ];
    }

    public function stockTurnover(array $filters): array
    {
        $tenantId = $filters['tenant_id'];
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $products = DB::table('products')
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'qtd_stock')
            ->orderBy('name')
            ->get();

        $entradaQuery = DB::table('stock_movements')
            ->where('tenant_id', $tenantId)
            ->where('type', 'entrada')
            ->select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id');

        $saidaQuery = DB::table('stock_movements')
            ->where('tenant_id', $tenantId)
            ->where('type', 'saida')
            ->select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id');

        if ($startDate) {
            $entradaQuery->whereDate('created_at', '>=', $startDate);
            $saidaQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $entradaQuery->whereDate('created_at', '<=', $endDate);
            $saidaQuery->whereDate('created_at', '<=', $endDate);
        }

        $entradas = $entradaQuery->pluck('total', 'product_id');
        $saidas = $saidaQuery->pluck('total', 'product_id');

        $rows = $products->map(function ($product) use ($entradas, $saidas) {
            $entrada = (float) ($entradas[$product->id] ?? 0);
            $saida = (float) ($saidas[$product->id] ?? 0);
            $stock = (float) $product->qtd_stock;
            $avgStock = max($stock, 1);
            $turnover = $saida > 0 ? round($saida / $avgStock, 2) : 0;

            return [
                'product' => $product->name,
                'current_stock' => $stock,
                'entries' => round($entrada, 3),
                'exits' => round($saida, 3),
                'turnover_index' => $turnover,
            ];
        })->filter(fn ($row) => $row['entries'] > 0 || $row['exits'] > 0 || $row['current_stock'] > 0);

        return [
            'summary' => [
                'products_count' => $rows->count(),
                'total_exits' => round($rows->sum('exits'), 3),
            ],
            'rows' => $rows->values()->all(),
        ];
    }

    private function baseSaleOrderQuery(array $filters)
    {
        $query = SaleOrder::query()
            ->where('tenant_id', $filters['tenant_id'])
            ->where('status', '!=', 'cancelado');

        if (!empty($filters['start_date'])) {
            $query->whereDate('ordered_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('ordered_at', '<=', $filters['end_date']);
        }

        return $query;
    }
}
