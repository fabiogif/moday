<?php

namespace App\Reports\Queries;

use App\Models\Product;
use App\Models\OrderProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopProductsReportQuery
{
    public function execute(array $filters): Collection
    {
        $query = DB::table('order_product')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->select(
                'products.id',
                'products.name',
                'products.price',
                DB::raw('SUM(order_product.qty) as total_quantity'),
                DB::raw('SUM(order_product.qty * order_product.price) as total_value'),
                DB::raw('AVG(order_product.price) as average_price'),
                DB::raw('MAX(orders.created_at) as last_sale')
            );
        
        // Filtro por tenant
        if (isset($filters['tenant_id'])) {
            $query->where('products.tenant_id', $filters['tenant_id']);
        }
        
        // Filtro por período
        if (isset($filters['start_date'])) {
            $query->whereDate('orders.created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->whereDate('orders.created_at', '<=', $filters['end_date']);
        }
        
        // Filtro por categoria
        if (isset($filters['category_id'])) {
            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                ->where('category_product.category_id', $filters['category_id']);
        }
        
        $query->groupBy('products.id', 'products.name', 'products.price')
            ->orderBy('total_quantity', 'desc');
        
        // Limite de produtos
        $limit = $filters['limit'] ?? 20;
        $query->limit($limit);
        
        return $query->get();
    }
}

