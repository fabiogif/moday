<?php

namespace Tests\Feature\Email;

use App\Mail\OrderCompletedMail;
use App\Mail\OrderReceiptMail;
use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OrderEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderEmailTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Client $client;
    private Order $order;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->accessible()->create([
            'name' => 'Restaurante Teste',
            'slug' => 'restaurante-teste',
        ]);

        $this->user = User::factory()->for($this->tenant)->create();
        $this->token = auth('api')->login($this->user);

        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria Cliente',
            'email' => 'maria.cliente@example.com',
            'phone' => '71999998888',
        ]);

        $paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'PIX',
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hambúrguer',
            'price' => 25.00,
        ]);

        $this->order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'identify' => 'PED12345',
            'total' => 50.00,
            'status' => 'Em Preparo',
            'payment_method' => 'PIX',
            'payment_method_id' => $paymentMethod->uuid,
            'is_delivery' => false,
        ]);

        $this->order->products()->attach($product->id, [
            'qty' => 2,
            'price' => 25.00,
        ]);
    }

    #[Test]
    public function it_sends_order_receipt_email_to_client(): void
    {
        Mail::fake();

        app(OrderEmailService::class)->sendOrderReceiptEmail($this->order);

        Mail::assertSent(OrderReceiptMail::class, function (OrderReceiptMail $mail) {
            return $mail->hasTo('maria.cliente@example.com')
                && $mail->order->identify === 'PED12345';
        });
    }

    #[Test]
    public function it_sends_order_completed_email_to_client(): void
    {
        Mail::fake();

        app(OrderEmailService::class)->sendOrderCompletedEmail($this->order);

        Mail::assertSent(OrderCompletedMail::class, function (OrderCompletedMail $mail) {
            return $mail->hasTo('maria.cliente@example.com')
                && $mail->order->identify === 'PED12345';
        });
    }

    #[Test]
    public function it_does_not_send_receipt_when_client_has_no_email(): void
    {
        Mail::fake();

        $this->client->update(['email' => '']);
        $this->order->refresh();

        $sent = app(OrderEmailService::class)->sendOrderReceiptEmail($this->order);

        $this->assertFalse($sent);
        Mail::assertNothingSent();
    }

    #[Test]
    public function api_can_send_receipt_email_for_order(): void
    {
        Mail::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/order/'.$this->order->identify.'/receipt/email');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'maria.cliente@example.com');

        Mail::assertSent(OrderReceiptMail::class);
    }

    #[Test]
    public function api_rejects_receipt_email_when_client_has_no_email(): void
    {
        Mail::fake();

        $this->client->update(['email' => '']);
        $this->order->refresh();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/order/'.$this->order->identify.'/receipt/email');

        $response->assertStatus(422);
        Mail::assertNothingSent();
    }
}
