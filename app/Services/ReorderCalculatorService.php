<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReorderSuggestion;
use App\Models\SaleOrderItem;
use App\Repositories\Contracts\ReorderSuggestionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderCalculatorService
{
    private const ANALYSIS_DAYS   = 30;
    private const TARGET_COVERAGE = 30;
    private const DEFAULT_LEAD_TIME = 7;

    public function __construct(
        private readonly ReorderSuggestionRepositoryInterface $suggestionRepository,
    ) {}

    public function listPending(int $tenantId): Collection
    {
        return $this->suggestionRepository->listPendingForTenant($tenantId);
    }

    public function generateForTenant(int $tenantId): int
    {
        $since          = Carbon::now()->subDays(self::ANALYSIS_DAYS);
        $salesByProduct = $this->aggregateSalesByProduct($tenantId, $since);

        $products = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('id', 'name', 'sku', 'qtd_stock', 'min_stock', 'safety_stock', 'reorder_point', 'price_cost')
            ->get();

        $created = 0;
        $now     = now();

        DB::beginTransaction();

        foreach ($products as $product) {
            $totalSold  = (float) ($salesByProduct[$product->id] ?? 0);
            $dailyAvg   = $totalSold / self::ANALYSIS_DAYS;

            if ($dailyAvg <= 0) {
                continue;
            }

            $available    = $product->availableStock();
            $coverageDays = (int) floor($available / $dailyAvg);
            $leadTime     = self::DEFAULT_LEAD_TIME;
            $suggestedQty = (int) ceil($dailyAvg * (self::TARGET_COVERAGE + $leadTime)) - $available;

            if ($suggestedQty <= 0) {
                continue;
            }

            $urgency = $this->classifyUrgency($coverageDays, $leadTime);

            $this->suggestionRepository->upsert($tenantId, $product->id, [
                'daily_avg_sales'    => round($dailyAvg, 4),
                'days_of_coverage'   => $coverageDays,
                'lead_time_days'     => $leadTime,
                'suggested_quantity' => $suggestedQty,
                'estimated_cost'     => $product->price_cost ? round($product->price_cost * $suggestedQty, 2) : null,
                'urgency'            => $urgency,
                'calculated_at'      => $now,
                'is_dismissed'       => false,
                'po_created'         => false,
            ]);

            $created++;
        }

        DB::commit();

        return $created;
    }

    public function createPurchaseOrder(int $tenantId, int $suggestionId): PurchaseOrder
    {
        $suggestion = $this->suggestionRepository->findForTenant($tenantId, $suggestionId);

        return DB::transaction(function () use ($suggestion, $tenantId) {
            $po = PurchaseOrder::create([
                'tenant_id'   => $tenantId,
                'supplier_id' => $suggestion->supplier_id,
                'status'      => 'rascunho',
                'notes'       => 'Gerado automaticamente pela sugestão de reposição em ' . now()->format('d/m/Y H:i'),
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id'        => $suggestion->product_id,
                'quantity_ordered'  => $suggestion->suggested_quantity,
                'quantity_received' => 0,
                'unit_cost'         => $suggestion->product?->price_cost ?? 0,
                'subtotal'          => ($suggestion->product?->price_cost ?? 0) * $suggestion->suggested_quantity,
            ]);

            $suggestion->update(['po_created' => true, 'purchase_order_id' => $po->id]);

            return $po->load('items.product');
        });
    }

    public function dismiss(int $tenantId, int $suggestionId): void
    {
        $this->suggestionRepository->findForTenant($tenantId, $suggestionId)
            ->update(['is_dismissed' => true]);
    }

    private function aggregateSalesByProduct(int $tenantId, Carbon $since): \Illuminate\Support\Collection
    {
        return SaleOrderItem::join('sale_orders', 'sale_order_items.sale_order_id', '=', 'sale_orders.id')
            ->where('sale_orders.tenant_id', $tenantId)
            ->where('sale_orders.status', '!=', 'cancelado')
            ->where('sale_orders.created_at', '>=', $since)
            ->where('sale_order_items.item_type', 'venda')
            ->groupBy('sale_order_items.product_id')
            ->selectRaw('sale_order_items.product_id, SUM(sale_order_items.quantity) as total_sold')
            ->pluck('total_sold', 'product_id');
    }

    private function classifyUrgency(int $coverageDays, int $leadTime): string
    {
        return match (true) {
            $coverageDays <= $leadTime     => 'critical',
            $coverageDays <= $leadTime * 2 => 'urgent',
            default                         => 'normal',
        };
    }
}
