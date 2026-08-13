<?php

namespace Tests\Unit\Adapters\Email;

use App\Adapters\Email\PostmarkAdapter;
use App\Mail\WelcomeCompanyMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PostmarkAdapterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o adaptador Postmark pode ser instanciado
     */
    public function test_postmark_adapter_can_be_instantiated(): void
    {
        $adapter = new PostmarkAdapter();

        $this->assertInstanceOf(PostmarkAdapter::class, $adapter);
    }

    /**
     * Testa se o adaptador Postmark retorna o nome correto do provedor
     */
    public function test_postmark_adapter_returns_correct_provider_name(): void
    {
        $adapter = new PostmarkAdapter();

        $this->assertEquals('postmark', $adapter->getProviderName());
    }

    /**
     * Testa se o adaptador Postmark verifica configuração corretamente
     */
    public function test_postmark_adapter_checks_configuration(): void
    {
        $adapter = new PostmarkAdapter();

        $isConfigured = $adapter->isConfigured();

        $this->assertIsBool($isConfigured);
    }
}






