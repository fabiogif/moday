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

/**
 * ATENÇÃO — este service cria DOIS registros para o mesmo pedido, de propósito:
 *
 * 1. `SaleOrder` (+ `SaleOrderItem`): o pedido de venda B2B oficial — desconto,
 *    limite de crédito, tabela de preço, faturamento/NF-e e vínculo com romaneio
 *    de entrega. Fluxo de status de logística de atacado (aprovado → separação
 *    → faturado → em trânsito → entregue).
 * 2. `Order` (+ `OrderProduct`), criado em `createDashboardOrder()`: alimenta o
 *    quadro Kanban operacional (`/orders/board` no frontend) usado pela
 *    cozinha/balcão para acompanhar o preparo em tempo real. Fluxo de status de
 *    preparo (pendente → preparo → pronto → entregue), sem relação com
 *    faturamento.
 *
 * Os dois registros compartilham o mesmo `identify` (mesmo número de pedido)
 * mas representam bounded contexts diferentes — um não substitui o outro. Isso
 * NÃO é duplicação por descuido: o quadro Kanban nunca foi adaptado para ler
 * direto de `SaleOrder`, então este service "espelha" o pedido B2B como um
 * `Order` legado só para a equipe de operação enxergá-lo no quadro.
 *
 * Antes de tentar unificar os dois modelos, note que os vocabulários de status
 * são conceitualmente distintos (logística de atacado vs. preparo de balcão) —
 * unificar exigiria redesenhar o quadro Kanban para consumir `SaleOrder`
 * diretamente, migração de dados e testes de regressão dedicados. Decisão
 * registrada em 2026-07-22 (auditoria de arquitetura): manter os dois
 * domínios separados por ora.
 */
class PublicOrderService
{
    public function __construct(
        private readonly PublicClientService $clientService,
        private readonly PublicOrderCalculationService $calculationService,
        private readonly WhatsAppService $whatsAppService,
        private readonly CouponService $couponService,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly CacheService $cacheService,
        private readonly OrderEmailService $orderEmailService,
    ) {}

    public function createOrder(array $validatedData, Tenant $tenant): array
    {
        // Transação cobre apenas operações críticas de banco de dados
        [$saleOrder, $client, $couponResult] = DB::transaction(function () use ($validatedData, $tenant) {
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

            try {
                $this->createDashboardOrder($saleOrder, $client, $tenant, $validatedData, $paymentMethod, $couponResult, $calculation);
            } catch (\Exception $e) {
                \Log::error('PublicOrderService: createDashboardOrder failed', [
                    'sale_order_id' => $saleOrder->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            return [$saleOrder, $client, $couponResult];
        });

        // Efeitos colaterais fora da transação para não corromper o commit
        $saleOrder->loadMissing(['items.product', 'client']);
        $this->sendCompletionEmail($tenant, $saleOrder);
        $this->broadcastSaleOrderCreated($saleOrder);
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

        $deliveryData = [
            'is_delivery'     => !empty($delivery['is_delivery']),
            'origin'          => 'store',
            'shipping_method' => $validatedData['shipping_method'] ?? null,
        ];
        if (!empty($delivery['is_delivery'])) {
            $deliveryData = array_merge($deliveryData, [
                'delivery_address'      => $delivery['address'] ?? null,
                'delivery_city'         => $delivery['city'] ?? null,
                'delivery_state'        => $delivery['state'] ?? null,
                'delivery_zip_code'     => $delivery['zip_code'] ?? null,
                'delivery_neighborhood'  => $delivery['neighborhood'] ?? null,
                'delivery_number'       => $delivery['number'] ?? null,
                'delivery_complement'   => $delivery['complement'] ?? null,
                'delivery_notes'        => $delivery['notes'] ?? null,
                'use_client_address'    => false,
            ]);
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

        // Attach products via pivot (aggregate quantities for duplicate products)
        $aggregated = [];
        foreach ($calculation['products'] as $product) {
            $pid = $product['product_id'];
            if (isset($aggregated[$pid])) {
                $aggregated[$pid]['qty'] += (float) $product['quantity'];
            } else {
                $aggregated[$pid] = [
                    'product_id' => $pid,
                    'qty'        => (float) $product['quantity'],
                    'price'      => (float) $product['price'],
                ];
            }
        }
        foreach ($aggregated as $item) {
            OrderProduct::create([
                'order_id'   => $order->id,
                'product_id' => $item['product_id'],
                'qty'        => $item['qty'],
                'price'      => $item['price'],
            ]);
        }

        $this->cacheService->invalidateOrderCache($tenant->id);
    }

    private function sendCompletionEmail(Tenant $tenant, SaleOrder $saleOrder): void
    {
        try {
            if ($tenant->plan && $tenant->plan->has_order_completion_email) {
                $order = Order::where('identify', $saleOrder->identify)
                    ->where('tenant_id', $tenant->id)
                    ->first();

                if ($order) {
                    $this->orderEmailService->sendOrderCompletedEmail($order);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('PublicOrderService: falha ao enviar e-mail de confirmação', [
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
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

    private function broadcastSaleOrderCreated(SaleOrder $order): void
    {
        try {
            \App\Events\SaleOrderCreated::dispatch($order);
        } catch (\Exception $e) {
            \Log::warning('PublicOrderService: falha ao broadcast SaleOrderCreated', [
                'sale_order_id' => $order->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
