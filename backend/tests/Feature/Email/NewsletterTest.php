<?php

namespace Tests\Feature\Email;

use App\Jobs\SendNewsletterEmailJob;
use App\Models\AdminUser;
use App\Models\NewsletterSubscriber;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin()
    {
        $admin = AdminUser::factory()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ]);
    }

    public function test_public_can_subscribe_to_newsletter(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'novo@teste.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'novo@teste.com',
        ]);
    }

    public function test_resubscribe_reactivates_unsubscribed_email(): void
    {
        NewsletterSubscriber::create([
            'email' => 'retorno@teste.com',
            'subscribed_at' => now()->subMonth(),
            'unsubscribed_at' => now()->subWeek(),
        ]);

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'retorno@teste.com',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'retorno@teste.com',
            'unsubscribed_at' => null,
        ]);
    }

    public function test_admin_can_queue_newsletter_emails(): void
    {
        Queue::fake();

        NewsletterSubscriber::create([
            'email' => 'inscrito@teste.com',
            'subscribed_at' => now(),
        ]);

        $response = $this->actingAsAdmin()->postJson('/api/admin/email/send-bulk', [
            'send_to_newsletter' => true,
            'send_to_tenants' => false,
            'subject' => 'Novidades Alba Tec',
            'message' => 'Confira as novidades da plataforma.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queued', 1);

        Queue::assertPushed(SendNewsletterEmailJob::class, function (SendNewsletterEmailJob $job) {
            return $job->emailTo === 'inscrito@teste.com';
        });
    }

    public function test_admin_can_send_to_newsletter_and_tenants(): void
    {
        Queue::fake();

        NewsletterSubscriber::create([
            'email' => 'inscrito@teste.com',
            'subscribed_at' => now(),
        ]);

        $tenant = Tenant::factory()->create(['email' => 'empresa@teste.com']);

        $response = $this->actingAsAdmin()->postJson('/api/admin/email/send-bulk', [
            'send_to_newsletter' => true,
            'send_to_tenants' => true,
            'tenant_ids' => [$tenant->id],
            'subject' => 'Atualização',
            'message' => 'Mensagem para todos.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.queued', 2);
    }

    public function test_admin_can_list_newsletter_subscribers(): void
    {
        NewsletterSubscriber::create([
            'email' => 'lista@teste.com',
            'subscribed_at' => now(),
        ]);

        $response = $this->actingAsAdmin()->getJson('/api/admin/email/newsletter-subscribers');

        $response->assertOk()
            ->assertJsonPath('data.stats.active', 1)
            ->assertJsonPath('data.subscribers.0.email', 'lista@teste.com');
    }
}
