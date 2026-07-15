<?php

namespace Tests\Feature\Email;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_addresses_use_distribtec_defaults(): void
    {
        $this->assertEquals('contato@distribtec.com.br', config('mail.contact_to'));
        $this->assertEquals('atendimento@distribtec.com.br', config('mail.support_to'));
        $this->assertEquals('pix@distribtec.com.br', config('mail.pix_to'));
    }

    public function test_smtp_encryption_null_is_supported_in_config(): void
    {
        config(['mail.mailers.smtp.encryption' => null]);

        $this->assertNull(config('mail.mailers.smtp.encryption'));
    }
}
