<?php

namespace Tests\Unit\Services;

use App\Services\MercadoPagoService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    private MercadoPagoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mercadopago.access_token' => 'TEST-token-unit']);
        $this->service = new MercadoPagoService();
    }

    public function test_createCardPayment_returns_success_when_payment_approved(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders' => Http::response([
                'id'     => 'order_123',
                'status' => 'processed',
                'transactions' => [
                    'payments' => [[
                        'id'            => 'pay_456',
                        'status'        => 'processed',
                        'status_detail' => 'accredited',
                    ]],
                ],
            ], 201),
        ]);

        $result = $this->service->createCardPayment(
            amount:            99.90,
            token:             'card_token_abc',
            paymentMethodId:   'visa',
            paymentTypeId:     'credit_card',
            installments:      1,
            payerEmail:        'test@example.com',
            externalReference: 'tenant_1_plan_1_' . time(),
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('order_123', $result['order_id']);
        $this->assertEquals('processed', $result['payment_status']);
        $this->assertEquals('pay_456', $result['transaction_id']);
        $this->assertNull($result['error']);
    }

    public function test_createCardPayment_returns_failure_when_payment_rejected(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders' => Http::response([
                'id'     => 'order_789',
                'status' => 'processed',
                'transactions' => [
                    'payments' => [[
                        'id'            => 'pay_789',
                        'status'        => 'rejected',
                        'status_detail' => 'cc_rejected_insufficient_amount',
                    ]],
                ],
            ], 201),
        ]);

        $result = $this->service->createCardPayment(
            amount:            99.90,
            token:             'bad_token',
            paymentMethodId:   'mastercard',
            paymentTypeId:     'credit_card',
            installments:      1,
            payerEmail:        'test@example.com',
            externalReference: 'tenant_1_plan_1_' . time(),
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('cc_rejected_insufficient_amount', $result['error']);
    }

    public function test_createCardPayment_returns_failure_on_http_error(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $result = $this->service->createCardPayment(
            amount:            99.90,
            token:             'invalid_token',
            paymentMethodId:   'visa',
            paymentTypeId:     'credit_card',
            installments:      1,
            payerEmail:        'test@example.com',
            externalReference: 'tenant_1_plan_1_' . time(),
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Unauthorized', $result['error']);
    }

    public function test_createCardPayment_sends_correct_payload(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders' => Http::response([
                'id'     => 'order_ok',
                'status' => 'processed',
                'transactions' => ['payments' => [['id' => 'p1', 'status' => 'approved', 'status_detail' => 'accredited']]],
            ], 201),
        ]);

        $this->service->createCardPayment(
            amount:            49.90,
            token:             'tkn_xyz',
            paymentMethodId:   'elo',
            paymentTypeId:     'credit_card',
            installments:      3,
            payerEmail:        'buyer@test.com',
            externalReference: 'tenant_5_plan_2_9999',
        );

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            return $request->url() === 'https://api.mercadopago.com/v1/orders'
                && $body['total_amount'] === '49.90'
                && $body['external_reference'] === 'tenant_5_plan_2_9999'
                && $body['payer']['email'] === 'buyer@test.com'
                && $body['transactions']['payments'][0]['payment_method']['id'] === 'elo'
                && $body['transactions']['payments'][0]['payment_method']['installments'] === 3
                && $body['processing_mode'] === 'automatic';
        });
    }

    public function test_getOrder_returns_order_data_on_success(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders/order_abc' => Http::response([
                'id'     => 'order_abc',
                'status' => 'processed',
            ], 200),
        ]);

        $result = $this->service->getOrder('order_abc');

        $this->assertTrue($result['success']);
        $this->assertEquals('order_abc', $result['data']['id']);
    }

    public function test_getOrder_returns_failure_when_not_found(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders/nonexistent' => Http::response([], 404),
        ]);

        $result = $this->service->getOrder('nonexistent');

        $this->assertFalse($result['success']);
        $this->assertEquals('Order não encontrada', $result['error']);
    }
}
