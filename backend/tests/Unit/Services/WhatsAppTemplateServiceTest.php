<?php

namespace Tests\Unit\Services;

use App\Services\WhatsAppTemplateService;
use Tests\TestCase;

class WhatsAppTemplateServiceTest extends TestCase
{
    public function test_format_phone_number_adds_brazil_country_code(): void
    {
        $this->assertSame('5511987654321', WhatsAppTemplateService::formatPhoneNumber('(11) 98765-4321'));
        $this->assertSame('5511987654321', WhatsAppTemplateService::formatPhoneNumber('11987654321'));
        $this->assertSame('551133334444', WhatsAppTemplateService::formatPhoneNumber('1133334444'));
    }

    public function test_format_phone_number_keeps_existing_country_code(): void
    {
        $this->assertSame('5511987654321', WhatsAppTemplateService::formatPhoneNumber('5511987654321'));
    }
}
