<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Repositories\contracts\OrderRepositoryInterface;
use App\Repositories\contracts\PaginateRepositoryInterface;
use App\Repositories\contracts\Presenter\PaginatePresenter;
use Illuminate\Database\Eloquent\Model;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(
        protected Model $entity = new Order(),
        protected Model $entityOrderProduct = new OrderProduct()
    ){}

    public function createNewOrder(string $identify, float $total, string $status, int $tenantId, ?string $comment = null, $clientId = null, $tableId = null, array $deliveryData = [])
    {

        $order = [
            'identify' => $identify,
            'total' => $total,
            'status' => $status,
            'origin' => 'admin', // Default origin is admin panel
            'tenant_id' => $tenantId,
            'comment' => $comment,
        ];

        if($clientId) $order['client_id'] = $clientId;
        if($tableId) $order['table_id'] = $tableId;
        
        // Add delivery data if provided
        if (!empty($deliveryData)) {
            $order = array_merge($order, $deliveryData);
        }

        return $this->entity->create($order);

    }

    public function getOrderByIdentify($identify):Order|null
    {
        return $this->entity->where('identify', $identify)->first();
    }

    public function findByIdentify($identify): Order|null
    {
        return $this->entity->where('identify', $identify)->first();
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
        $query = $this->entity->with(['client', 'table', 'products', 'tenant'])
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
        $order->load(['client', 'table', 'products', 'tenant']);
        
        return $order;
    }

}
