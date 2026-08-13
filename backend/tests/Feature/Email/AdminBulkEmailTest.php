<?php

namespace Tests\Feature\Email;

use App\Jobs\SendBulkEmailJob;
use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminBulkEmailTest extends TestCase
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

    public function test_admin_can_queue_bulk_emails_for_tenants(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create(['email' => 'loja@teste.com']);

        $response = $this->actingAsAdmin()->postJson('/api/admin/email/send-bulk', [
            'tenant_ids' => [$tenant->id],
            'subject' => 'Atualização do sistema',
            'message' => 'Prezado {name}, confira as novidades.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queued', 1);

        Queue::assertPushed(SendBulkEmailJob::class, function (SendBulkEmailJob $job) {
            return $job->emailTo === 'loja@teste.com'
                && $job->subject === 'Atualização do sistema';
        });
    }

    public function test_admin_bulk_email_requires_valid_tenant_ids(): void
    {
        Queue::fake();

        $response = $this->actingAsAdmin()->postJson('/api/admin/email/send-bulk', [
            'tenant_ids' => [99999],
            'subject' => 'Teste',
            'message' => 'Mensagem',
        ]);

        $response->assertStatus(422);
        Queue::assertNothingPushed();
    }
}
