<?php

namespace App\Services;

use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PublicOrderService
{
    public function __construct(
        private readonly PublicClientService $clientService,
        private readonly PublicOrderCalculationService $calculationService,
        private readonly WhatsAppService $whatsAppService,
        private readonly CouponService $couponService,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly CacheService $cacheService,
        private readonly NotificationService $notificationService,
    ) {}

    public function createOrder(array $validatedData, Tenant $tenant): array
    {
        return DB::transaction(function () use ($validatedData, $tenant) {
            $client = $this->clientService->createOrUpdateClient(
                $validatedData['client'],
                $tenant,
                $validatedData['delivery'] ?? null
            );

            $calculation = $this->calculationService->calculateOrder(
                $validatedData['products'],
                $tenant
            );

            $couponResult = $this->couponService->applyCouponForOrder(
                $tenant,
                $validatedData['coupon_code'] ?? null,
                $calculation['total'],
                [
                    'client_id'         => $client->id ?? null,
                    'client_identifier' => $validatedData['client']['email'] ?? $validatedData['client']['phone'] ?? null,
                ]
            );

            $paymentMethod = $this->resolvePaymentMethod($validatedData['payment_method'], $tenant);

            $saleOrder = $this->createSaleOrder(
                $client,
                $tenant,
                $validatedData,
                $paymentMethod,
                $couponResult
            );

            $this->createSaleOrderItems($saleOrder->id, $calculation['products']);

            $this->cacheService->invalidateSaleOrderCache($tenant->id);

            // Também cria um registro na tabela orders para aparecer no dashboard
            $this->createDashboardOrder($saleOrder, $client, $tenant, $validatedData, $paymentMethod, $couponResult, $calculation);

            $saleOrder->loadMissing('items.product');

            // Notifica usuários do tenant sobre o novo pedido
            $this->notifyTenantUsers($saleOrder, $client, $tenant);

            $whatsAppData = $this->generateWhatsAppData($saleOrder, $client, $tenant);

            return [
                'order'           => $saleOrder,
                'subtotal'        => $couponResult['subtotal'],
                'discount_amount' => $couponResult['discount_amount'],
                'total'           => $couponResult['final_total'],
                'coupon_code'     => $couponResult['coupon']?->code,
                'whatsapp_message' => $whatsAppData['message'],
                'whatsapp_link'    => $whatsAppData['link'],
            ];
        });
    }

    private function createSaleOrder(
        $client,
        Tenant $tenant,
        array $validatedData,
        ?PaymentMethod $paymentMethod,
        array $couponResult
    ): SaleOrder {
        $delivery     = $validatedData['delivery'];
        $shippingData = $this->buildShippingData($delivery);
        $notes        = $delivery['notes'] ?? null;

        return SaleOrder::create(array_merge([
            'tenant_id'       => $tenant->id,
            'client_id'       => $client->id,
            'status'          => 'orcamento',
            'subtotal'        => $couponResult['subtotal'],
            'discount_amount' => $couponResult['discount_amount'],
            'freight_amount'  => 0,
            'tax_amount'      => 0,
            'total'           => $couponResult['final_total'],
            'payment_method'  => $paymentMethod?->name,
            'notes'           => $notes,
        ], $shippingData));
    }

    private function createSaleOrderItems(int $saleOrderId, array $products): void
    {
        foreach ($products as $product) {
            $qty      = (float) $product['quantity'];
            $price    = (float) $product['price'];
            $subtotal = $qty * $price;

            SaleOrderItem::create([
                'sale_order_id'    => $saleOrderId,
                'product_id'       => $product['product_id'],
                'item_type'        => 'venda',
                'quantity'         => $qty,
                'unit_price'       => $price,
                'discount_percent' => 0,
                'subtotal'         => $subtotal,
                'tax_amount'       => 0,
            ]);
        }
    }

    private function createDashboardOrder(
        SaleOrder $saleOrder,
        $client,
        Tenant $tenant,
        array $validatedData,
        ?PaymentMethod $paymentMethod,
        array $couponResult,
        array $calculation
    ): void {
        $delivery = $validatedData['delivery'] ?? [];

        $deliveryData = [];
        if (!empty($delivery['is_delivery'])) {
            $deliveryData = [
                'is_delivery'           => true,
                'delivery_address'      => $delivery['address'] ?? null,
                'delivery_city'         => $delivery['city'] ?? null,
                'delivery_state'        => $delivery['state'] ?? null,
                'delivery_zip_code'     => $delivery['zip_code'] ?? null,
                'delivery_neighborhood'  => $delivery['neighborhood'] ?? null,
                'delivery_number'       => $delivery['number'] ?? null,
                'delivery_complement'   => $delivery['complement'] ?? null,
                'delivery_notes'        => $delivery['notes'] ?? null,
                'use_client_address'    => false,
                'origin'                => 'store',
            ];
        } else {
            $deliveryData['origin'] = 'store';
        }

        $initialStatus = OrderStatus::where('tenant_id', $tenant->id)
            ->where('is_initial', true)
            ->first();

        $coupon = $couponResult['coupon'] ?? null;

        $order = Order::create(array_merge([
            'tenant_id'        => $tenant->id,
            'identify'         => $saleOrder->identify,
            'client_id'        => $client->id,
            'status'           => $initialStatus?->name ?? 'Em Preparo',
            'order_status_id'  => $initialStatus?->id,
            'origin'           => 'store',
            'subtotal'         => $couponResult['subtotal'],
            'discount_amount'  => $couponResult['discount_amount'],
            'total'            => $couponResult['final_total'],
            'payment_method'   => $paymentMethod?->name,
            'payment_method_id' => $paymentMethod?->uuid,
            'coupon_id'        => $coupon?->id,
            'coupon_code'      => $coupon?->code,
            'coupon_name'      => $coupon?->name,
            'comment'          => $delivery['notes'] ?? null,
        ], $deliveryData));

        // Attach products via pivot
        foreach ($calculation['products'] as $product) {
            OrderProduct::create([
                'order_id'   => $order->id,
                'product_id' => $product['product_id'],
                'qty'        => (float) $product['quantity'],
                'price'      => (float) $product['price'],
            ]);
        }

        $this->cacheService->invalidateOrderCache($tenant->id);
    }

    private function buildShippingData(array $delivery): array
    {
        if (!$delivery['is_delivery'] || empty($delivery['address'])) {
            return [
                'use_client_address' => false,
                'shipping_address'   => null,
                'shipping_city'      => null,
                'shipping_state'     => null,
                'shipping_zipcode'   => null,
            ];
        }

        return [
            'use_client_address' => false,
            'shipping_address'   => array_filter([
                'street'       => $delivery['address'],
                'number'       => $delivery['number'] ?? null,
                'neighborhood' => $delivery['neighborhood'] ?? null,
                'complement'   => $delivery['complement'] ?? null,
            ]),
            'shipping_city'    => $delivery['city'] ?? null,
            'shipping_state'   => $delivery['state'] ?? null,
            'shipping_zipcode' => $delivery['zip_code'] ?? null,
        ];
    }

    private function resolvePaymentMethod(string $uuid, Tenant $tenant): ?PaymentMethod
    {
        $method = $this->paymentMethodRepository->getPaymentMethodByUuid($uuid);
        return ($method && $method->tenant_id === $tenant->id) ? $method : null;
    }

    private function generateWhatsAppData(SaleOrder $order, $client, Tenant $tenant): array
    {
        $message = $this->whatsAppService->generateSaleOrderMessage($order, $client, $tenant);
        $phone   = $tenant->whatsapp ?? $tenant->phone;
        $link    = $this->whatsAppService->generateLink($phone, $message);

        return ['message' => $message, 'link' => $link];
    }

    private function notifyTenantUsers(SaleOrder $order, $client, Tenant $tenant): void
    {
        try {
            $users = \App\Models\User::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            foreach ($users as $user) {
                $this->notificationService->send(
                    $user->id,
                    $tenant->id,
                    'order_created',
                    [
                        'title'          => 'Novo Pedido de Venda',
                        'message'        => "Pedido #{$order->identify} de {$client->name} — R$ " .
                                            number_format((float) $order->total, 2, ',', '.'),
                        'order_id'       => $order->identify,
                        'order_total'    => $order->total,
                        'client_name'    => $client->name,
                        'notifiable_type' => SaleOrder::class,
                        'notifiable_id'  => $order->id,
                    ]
                );
            }
        } catch (\Exception $e) {
            \Log::warning('PublicOrderService: falha ao notificar usuários', [
                'sale_order_id' => $order->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
