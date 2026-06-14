<?php

namespace Tests\Feature\Integrations;

use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\IfoodOauthSessionRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class IfoodOAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        Config::set('services.ifood.client_id', 'client-123');
        Config::set('services.ifood.oauth_url', 'https://merchant-api.ifood.com.br/authentication/v1.0');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_it_requests_user_code_and_stores_session(): void
    {
        $tenant = Tenant::factory()->create();
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        Http::fake([
            'https://merchant-api.ifood.com.br/authentication/v1.0/oauth/userCode' => Http::response([
                'userCode' => 'ABCD-1234',
                'authorizationCodeVerifier' => 'verifier-token',
                'verificationUrl' => 'https://portal.ifood.com.br/apps/code',
                'verificationUrlComplete' => 'https://portal.ifood.com.br/apps/code?c=ABCD-1234',
                'expiresIn' => 600,
            ], 200),
        ]);

        $response = $this->postJson('/api/integrations/ifood/oauth/user-code', [
            'tenant_id' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Código de autorização iFood gerado com sucesso. Solicite ao merchant que conclua a autorização no portal.',
            ])
            ->assertJsonFragment([
                'user_code' => 'ABCD-1234',
                'verification_url' => 'https://portal.ifood.com.br/apps/code',
                'verification_url_complete' => 'https://portal.ifood.com.br/apps/code?c=ABCD-1234',
                'expires_in' => 600,
            ]);

        $repository = $this->app->make(IfoodOauthSessionRepositoryInterface::class);
        $session = $repository->findByUserCode('ABCD-1234', $tenant->id);

        $this->assertNotNull($session);
        $this->assertEquals('pending', $session->status);
        $this->assertEquals('verifier-token', $session->authorization_code_verifier);
    }

    public function test_it_requires_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/integrations/ifood/oauth/user-code');

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'tenant_id não informado.',
            ]);
    }

    public function test_it_requires_client_id_configuration(): void
    {
        Config::set('services.ifood.client_id', null);

        $tenant = Tenant::factory()->create();
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/integrations/ifood/oauth/user-code', [
            'tenant_id' => $tenant->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'IFOOD_CLIENT_ID não configurado.',
            ]);
    }
}

