<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Repositories\Contracts\OrderStatusRepositoryInterface;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicOrderService
{
    public function __construct(
        private readonly PublicClientService $clientService,
        private readonly PublicOrderCalculationService $calculationService,
        private readonly OrderIdentifierService $identifierService,
        private readonly WhatsAppService $whatsAppService,
        private readonly OrderStatusRepositoryInterface $orderStatusRepositoryInterface,
        private readonly CouponService $couponService,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly OrderEmailService $orderEmailService,
        private readonly CacheService $cacheService,
    ) {}

    /**
     * Create public order
     */
    public function createOrder(array $validatedData, Tenant $tenant): array
    {
        return DB::transaction(function () use ($validatedData, $tenant) {
            // Create or update client
            $client = $this->clientService->createOrUpdateClient(
                $validatedData['client'],
                $tenant,
                $validatedData['delivery'] ?? null
            );

            // Calculate order total and products
            $calculation = $this->calculationService->calculateOrder(
                $validatedData['products'],
                $tenant
            );

            // Apply coupon if provided
            $couponResult = $this->couponService->applyCouponForOrder(
                $tenant,
                $validatedData['coupon_code'] ?? null,
                $calculation['total'],
                [
                    'client_id' => $client->id ?? null,
                    'client_identifier' => $validatedData['client']['email'] ?? $validatedData['client']['phone'] ?? null,
                ]
            );

            // Get payment method
            $paymentMethod = $this->getPaymentMethod($validatedData['payment_method'], $tenant);

            // Prepare order data
            $orderData = $this->prepareOrderData($validatedData, $tenant, $client, $calculation, $paymentMethod, $couponResult);

            // Create order via repository
            /** @var Order $order */
            $order = $this->orderRepository->createNewOrder(
                identify: $orderData['identify'],
                total: $orderData['total'],
                status: $orderData['status'],
                tenantId: $orderData['tenant_id'],
                comment: $orderData['delivery_notes'] ?? null,
                clientId: $orderData['client_id'],
                tableId: null,
                deliveryData: array_diff_key($orderData, array_flip([
                    'identify',
                    'total',
                    'status',
                    'tenant_id',
                    'client_id',
                    'payment_method',
                    'coupon_id',
                    'coupon_code',
                    'coupon_name',
                    'coupon_snapshot',
                    'order_status_id',
                ])),
                paymentMethodId: $orderData['payment_method_id'] ?? null,
                orderStatusId: $orderData['order_status_id'] ?? null,
            );

            // Attach products and update stock
            $this->attachProductsAndUpdateStock($order, $calculation['products']);

            // Invalida cache de pedidos para que a listagem no dashboard reflita imediatamente
            $this->cacheService->invalidateOrderCache($tenant->id);

            // Register coupon redemption if applicable
            $this->couponService->registerRedemption($couponResult['coupon'], $order, $client->id ?? null);

            // Load relationships needed for WhatsApp message
            $order->load('paymentMethod');

            // Broadcast evento de novo pedido + atualiza dashboard em tempo real
            try {
                \App\Events\OrderCreated::dispatch($order);
                $this->dispatchDashboardUpdate($tenant->id);
            } catch (\Exception $e) {
                \Log::warning('PublicOrderService: falha ao disparar evento OrderCreated', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

            // Generate WhatsApp data
            $whatsAppData = $this->generateWhatsAppData($order, $client, $tenant);

            // E-mail de confirmação para o cliente (requer opção no plano)
            $tenant->loadMissing('plan');
            if ($tenant->plan?->hasFeature('order_completion_email')) {
                try {
                    $this->orderEmailService->sendOrderCompletedEmail($order);
                } catch (\Throwable $e) {
                    \Log::warning('PublicOrderService: Falha ao enviar e-mail de conclusão', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'order' => $order,
                'subtotal' => $couponResult['subtotal'],
                'discount_amount' => $couponResult['discount_amount'],
                'total' => $couponResult['final_total'],
                'coupon_code' => $couponResult['coupon']?->code,
                'whatsapp_message' => $whatsAppData['message'],
                'whatsapp_link' => $whatsAppData['link'],
            ];
        });
    }

    /**
     * Get payment method details
     */
    private function getPaymentMethod(string $paymentMethodUuid, Tenant $tenant): ?PaymentMethod
    {
        $method = $this->paymentMethodRepository->getPaymentMethodByUuid($paymentMethodUuid);

        if ($method && $method->tenant_id === $tenant->id) {
            return $method;
        }

        return null;
    }

    /**
     * Prepare order data
     */
    private function prepareOrderData(
        array $validatedData,
        Tenant $tenant,
        $client,
        array $calculation,
        ?PaymentMethod $paymentMethod,
        array $couponResult
    ): array {
        $initialStatus = $this->resolveInitialStatus($tenant->id);

        $orderData = [
            'identify' => $this->identifierService->generate(),
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'subtotal' => $couponResult['subtotal'],
            'discount_amount' => $couponResult['discount_amount'],
            'total' => $couponResult['final_total'],
            'status' => $this->resolveOrderStatusLabel($initialStatus),
            'order_status_id' => $initialStatus?->id,
            'origin' => 'public_store',
            'is_delivery' => $validatedData['delivery']['is_delivery'],
            'payment_method' => $paymentMethod ? $paymentMethod->name : 'Forma de pagamento não encontrada',
            'shipping_method' => $validatedData['shipping_method'],
            'coupon_id' => $couponResult['coupon']?->id,
            'coupon_code' => $couponResult['coupon']?->code,
            'coupon_name' => $couponResult['coupon']?->name,
            'coupon_snapshot' => $couponResult['coupon_snapshot'],
        ];

        // Add payment_method_id if column exists
        if (Schema::hasColumn('orders', 'payment_method_id')) {
            $orderData['payment_method_id'] = $validatedData['payment_method'];
        }

        // Add delivery fields if delivery is selected
        if ($validatedData['delivery']['is_delivery']) {
            $orderData = array_merge($orderData, [
                'delivery_address' => $validatedData['delivery']['address'],
                'delivery_number' => $validatedData['delivery']['number'],
                'delivery_neighborhood' => $validatedData['delivery']['neighborhood'],
                'delivery_city' => $validatedData['delivery']['city'],
                'delivery_state' => $validatedData['delivery']['state'],
                'delivery_zip_code' => $validatedData['delivery']['zip_code'],
                'delivery_complement' => $validatedData['delivery']['complement'] ?? null,
                'delivery_notes' => $validatedData['delivery']['notes'] ?? null,
            ]);
        }

        return $orderData;
    }

    private function resolveInitialStatus(int $tenantId): ?\App\Models\OrderStatus
    {
        if (!$tenantId) {
            return null;
        }

        $initialStatus = $this->orderStatusRepositoryInterface->getInitialStatus($tenantId);
        if ($initialStatus && $initialStatus->is_active) {
            return $initialStatus;
        }

        $firstStatus = $this->orderStatusRepositoryInterface->getFirstByOrder($tenantId);
        if ($firstStatus && $firstStatus->is_active) {
            return $firstStatus;
        }

        return null;
    }

    /**
     * Attach products to order and update stock
     */
    private function attachProductsAndUpdateStock(Order $order, array $products): void
    {
        $merged = [];

        foreach ($products as $product) {
            $productId = (int) $product['product_id'];
            $quantity = (int) $product['quantity'];
            $lineTotal = $quantity * (float) $product['price'];

            if (!isset($merged[$productId])) {
                $merged[$productId] = [
                    'product_id' => $productId,
                    'quantity' => 0,
                    'line_total' => 0.0,
                ];
            }

            $merged[$productId]['quantity'] += $quantity;
            $merged[$productId]['line_total'] += $lineTotal;
        }

        foreach ($merged as $product) {
            $quantity = $product['quantity'];
            $unitPrice = $quantity > 0 ? $product['line_total'] / $quantity : 0;

            $order->products()->attach($product['product_id'], [
                'qty' => $quantity,
                'price' => $unitPrice,
            ]);

            $this->productRepository->update(
                ['qtd_stock' => DB::raw('qtd_stock - ' . $quantity)],
                $product['product_id']
            );
        }
    }

    /**
     * Garante valor compatível com a coluna orders.status (ENUM legado ou VARCHAR).
     */
    private function resolveOrderStatusLabel(?\App\Models\OrderStatus $initialStatus): string
    {
        $legacyValues = [
            'Em Preparo',
            'Pronto',
            'Saiu para entrega',
            'A Caminho',
            'Entregue',
            'Concluído',
            'Cancelado',
            'Arquivado',
        ];

        $name = $initialStatus?->name ?? 'Em Preparo';

        if (in_array($name, $legacyValues, true)) {
            return $name;
        }

        if ($initialStatus?->is_final) {
            return str_contains(mb_strtolower((string) $initialStatus->slug), 'cancel')
                ? 'Cancelado'
                : 'Entregue';
        }

        return 'Em Preparo';
    }

    private function dispatchDashboardUpdate(int $tenantId): void
    {
        try {
            $currentMonth = \Carbon\Carbon::now()->startOfMonth();

            $metrics = [
                'total_orders'  => \App\Models\Order::where('tenant_id', $tenantId)
                    ->where('created_at', '>=', $currentMonth)
                    ->count(),
                'total_revenue' => \App\Models\Order::where('tenant_id', $tenantId)
                    ->where('created_at', '>=', $currentMonth)
                    ->sum('total'),
                'timestamp'     => now()->toISOString(),
            ];

            \App\Events\DashboardMetricsUpdated::dispatch($tenantId, $metrics);
        } catch (\Exception $e) {
            \Log::warning('PublicOrderService: falha ao atualizar métricas do dashboard', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate WhatsApp message and link
     */
    private function generateWhatsAppData(Order $order, $client, Tenant $tenant): array
    {
        // Garantir que produtos estão carregados com dados do pivot
        $order->load('products');
        
        $message = $this->whatsAppService->generateOrderMessage($order, $client, $tenant);
        $link = $this->whatsAppService->generateLink($tenant->phone, $message);

        return [
            'message' => $message,
            'link' => $link,
        ];
    }
}
