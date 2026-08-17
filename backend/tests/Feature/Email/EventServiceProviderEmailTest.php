<?php

namespace Tests\Feature\Email;

use App\Events\CompanyRegistered;
use App\Listeners\SendWelcomeCompanyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventServiceProviderEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registered_event_has_welcome_email_listener(): void
    {
        $provider = $this->app->getProvider(\App\Providers\EventServiceProvider::class);
        $this->assertInstanceOf(\App\Providers\EventServiceProvider::class, $provider);

        $reflection = new \ReflectionClass($provider);
        $property = $reflection->getProperty('listen');
        $property->setAccessible(true);
        $listen = $property->getValue($provider);

        $this->assertArrayHasKey(CompanyRegistered::class, $listen);
        $this->assertContains(SendWelcomeCompanyEmail::class, $listen[CompanyRegistered::class]);
    }
}
