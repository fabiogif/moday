<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(
        protected Model $entity = new Order(),
        protected Model $entityOrderProduct = new OrderProduct()
    ){}

    public function createNewOrder(string $identify, float $total, string $status, int $tenantId, ?string $comment = null, $clientId = null, $tableId = null, array $deliveryData = [], $paymentMethodId = null, ?int $orderStatusId = null)
    {

        $order = [
            'identify' => $identify,
            'total' => $total,
            'status' => $status,
            'origin' => $deliveryData['origin'] ?? 'admin',
            'tenant_id' => $tenantId,
            'comment' => $comment,
        ];

        if ($orderStatusId) {
            $order['order_status_id'] = $orderStatusId;
        }

        if($clientId) $order['client_id'] = $clientId;
        if($tableId) $order['table_id'] = $tableId;
        if($paymentMethodId && \Schema::hasColumn('orders', 'payment_method_id')) {
            $order['payment_method_id'] = $paymentMethodId;
        }
        
        // Add delivery data if provided
        if (!empty($deliveryData)) {
            $order = array_merge($order, $deliveryData);
        }

        return $this->entity->create($order);

    }

    public function getOrderByIdentify($identify):Order|null
    {
        return $this->entity->with(['client', 'table', 'products', 'tenant', 'orderStatus'])
            ->where('identify', $identify)
            ->first();
    }

    public function findByIdentify($identify): Order|null
    {
        return $this->entity->with(['orderStatus'])
            ->where('identify', $identify)
            ->first();
    }

    public function registerProductsOrder(int $orderId, array $products)
    {
        $order = $this->entity->find($orderId);

        $orderProducts = array();

        foreach($products as $product){
            $orderProducts[$product['id']] = [
                'qty' => $product['qty'],
                'price' => $product['price'],
            ];
        }

        $order->products()->attach($orderProducts);
    }

    public function registerProductOrderPure(int $orderId, array $products)
    {
        $orderProducts = array();
        foreach($products as $product){
            array_push($orderProducts, [
                'order_id' => $orderId,
                    'product_id' => $product['id'],
                    'qty' => $product['qty'],
                    'price' => $product['price'],
                ]);
        }
        $this->entityOrderProduct->insert($orderProducts);
    }

    public function getOrdersByClientId(int $clientId): PaginateRepositoryInterface
    {
        $result =  $this->entity->where('client_id', $clientId)
            ->paginate(perPage: 15, columns: ['*'], pageName: 'page', page: 1, total: null);
        $relationships = ['client', 'table', 'products', 'tenant', 'OrderEvaluation'];
        return new PaginatePresenter($result, $relationships);

    }

    public function paginateByTenant(int $tenantId, int $page, int $perPage, ?string $status = null): PaginateRepositoryInterface
    {
        $query = $this->entity->with(['client', 'table', 'products', 'tenant', 'orderStatus'])
            ->where('tenant_id', $tenantId);

        if ($status) {
            $query->where('status', $status);
        }

        $result = $query->orderByDesc('created_at')
            ->paginate(perPage: $perPage, columns: ['*'], pageName: 'page', page: $page, total: null);

        $relationships = ['client', 'table', 'products', 'tenant'];
        return new PaginatePresenter($result, $relationships);
    }

    public function updateOrder(string $identify, array $data): Order
    {
        $order = $this->getOrderByIdentify($identify);
        
        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }

        $order->update($data);
        
        // Recarregar o model com relacionamentos
        $order->refresh();
        $order->load(['client', 'table', 'products', 'tenant', 'orderStatus']);
        
        return $order;
    }

    public function delete(string $identify): bool
    {
        $order = $this->getOrderByIdentify($identify);
        
        if (!$order) {
            return false;
        }

        return $order->delete();
    }

    public function findRecentOrderByClientInfo(int $tenantId, ?string $cpf = null, ?string $phone = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->entity->with(['client', 'products', 'tenant', 'paymentMethod', 'orderStatus'])
            ->where('tenant_id', $tenantId)
            ->whereHas('client', function ($q) use ($cpf, $phone) {
                if ($cpf) {
                    $q->where('cpf', $cpf);
                }
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function archiveOrder(Order $order): Order
    {
        $order->update([
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        return $order->refresh();
    }

    public function getOpenOrdersByTable(int $tenantId, string $tableUuid): array
    {
        // Buscar a mesa pelo UUID
        $table = \App\Models\Table::where('uuid', $tableUuid)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$table) {
            return [];
        }

        // Buscar pedidos em aberto da mesa (não concluídos, não cancelados, não entregues)
        $orders = $this->entity->with(['client', 'table', 'products', 'orderStatus', 'paymentMethod'])
            ->where('tenant_id', $tenantId)
            ->where('table_id', $table->id)
            ->whereNotIn('status', ['Entregue', 'Concluído', 'Cancelado', 'Arquivado'])
            ->orderByDesc('created_at')
            ->get();

        return $orders->toArray();
    }

    public function getTodayOrders(int $tenantId): array
    {
        // Buscar pedidos do dia atual (desde meia-noite)
        $todayStart = now()->startOfDay();
        
        $orders = $this->entity->with(['client', 'table', 'products', 'orderStatus', 'paymentMethod'])
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $todayStart)
            ->whereNotIn('status', ['Cancelado', 'Arquivado'])
            ->orderByDesc('created_at')
            ->get();

        return $orders->toArray();
    }

    public function getFrequentlyBoughtTogether(int $tenantId, array $productIds, int $limit = 6): array
    {
        if (empty($productIds)) {
            return [];
        }

        $recommendations = $this->entity->newQuery()
            ->select(
                'products.id',
                'products.uuid',
                'products.name',
                'products.price',
                'products.promotional_price',
                'products.image',
                'products.image_url',
                'products.description',
                DB::raw('COUNT(DISTINCT orders.id) as frequency')
            )
            ->from('order_product as op1')
            ->join('order_product as op2', function ($join) use ($productIds) {
                $join->on('op1.order_id', '=', 'op2.order_id')
                    ->whereIn('op1.product_id', $productIds)
                    ->whereColumn('op1.product_id', '!=', 'op2.product_id');
            })
            ->join('products', 'op2.product_id', '=', 'products.id')
            ->join('orders', 'op1.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('products.is_active', true)
            ->where('products.qtd_stock', '>', 0)
            ->whereNotIn('products.id', $productIds)
            ->whereNotIn('orders.status', ['Cancelado', 'Arquivado'])
            ->groupBy('products.id', 'products.uuid', 'products.name', 'products.price', 'products.promotional_price', 'products.image', 'products.image_url', 'products.description')
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'uuid' => $item->uuid,
                    'identify' => $item->uuid,
                    'name' => $item->name,
                    'price' => (float) $item->price,
                    'promotional_price' => $item->promotional_price ? (float) $item->promotional_price : null,
                    'image' => $item->image,
                    'image_url' => $item->image_url,
                    'description' => $item->description,
                    'frequency' => (int) $item->frequency,
                ];
            })
            ->toArray();

        return $recommendations;
    }

    public function getBestSellers(int $tenantId, int $limit = 6, ?int $days = 30): array
    {
        $query = $this->entity->newQuery()
            ->select(
                'products.id',
                'products.uuid',
                'products.name',
                'products.price',
                'products.promotional_price',
                'products.image',
                'products.image_url',
                'products.description',
                DB::raw('SUM(order_product.qty) as total_sold')
            )
            ->from('order_product')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->where('products.tenant_id', $tenantId)
            ->where('products.is_active', true)
            ->where('products.qtd_stock', '>', 0)
            ->where('orders.tenant_id', $tenantId)
            ->whereNotIn('orders.status', ['Cancelado', 'Arquivado']);

        if ($days) {
            $query->where('orders.created_at', '>=', now()->subDays($days));
        }

        $bestSellers = $query
            ->groupBy('products.id', 'products.uuid', 'products.name', 'products.price', 'products.promotional_price', 'products.image', 'products.image_url', 'products.description')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'uuid' => $item->uuid,
                    'identify' => $item->uuid,
                    'name' => $item->name,
                    'price' => (float) $item->price,
                    'promotional_price' => $item->promotional_price ? (float) $item->promotional_price : null,
                    'image' => $item->image,
                    'image_url' => $item->image_url,
                    'description' => $item->description,
                    'total_sold' => (int) $item->total_sold,
                ];
            })
            ->toArray();

        return $bestSellers;
    }

    public function identifyExists(string $identify): bool
    {
        return $this->entity->where('identify', $identify)->exists();
    }

    public function getOrdersByClientIdWithRelations(int $clientId): \Illuminate\Support\Collection
    {
        return $this->entity->where('client_id', $clientId)
            ->with(['products', 'table'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findLastDeliveryOrderByClientId(int $clientId, int $tenantId): ?Order
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('client_id', $clientId)
            ->where('is_delivery', true)
            ->whereNotNull('delivery_address')
            ->where('delivery_address', '!=', '')
            ->orderByDesc('created_at')
            ->first();
    }

}
