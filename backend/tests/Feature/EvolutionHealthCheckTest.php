<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EvolutionHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_only_when_state_changes(): void
    {
        config(['services.evolution_api.url' => 'http://evolution.test', 'services.evolution_api.key' => 'k']);
        $plan = Plan::factory()->create(['has_whatsapp_notifications' => true]);
        Tenant::factory()->create(['plan_id' => $plan->id, 'evolution_instance' => 'rest-1', 'email' => 'dono@rest.test']);

        $state = 'close';
        Http::fake(function () use (&$state) {
            return Http::response(['instance' => ['instanceName' => 'rest-1', 'state' => $state]]);
        });
        Log::spy();

        $this->artisan('evolution:health')->assertFailed();   // caiu -> alerta
        $this->artisan('evolution:health')->assertFailed();   // continua caído -> sem novo alerta
        $state = 'open';
        $this->artisan('evolution:health')->assertSuccessful(); // voltou -> alerta de retorno

        Log::shouldHaveReceived('critical')->once();
        Log::shouldHaveReceived('info')->withArgs(fn ($msg) => str_contains($msg, 'reconectado'))->once();
    }
}
