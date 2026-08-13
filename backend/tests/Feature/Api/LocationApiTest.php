<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\State;
use App\Services\LocationService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationApiTest extends TestCase
{
    private State $sp;
    private State $rj;
    private City $saoPaulo;
    private City $campinas;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->sp = State::factory()->create([
            'ibge_code' => '35',
            'uf' => 'SP',
            'name' => 'São Paulo',
            'region' => 'Sudeste',
        ]);
        $this->rj = State::factory()->create([
            'ibge_code' => '33',
            'uf' => 'RJ',
            'name' => 'Rio de Janeiro',
            'region' => 'Sudeste',
        ]);

        $this->saoPaulo = City::factory()->forState($this->sp)->create([
            'ibge_code' => '3550308',
            'name' => 'São Paulo',
            'is_capital' => true,
        ]);
        $this->campinas = City::factory()->forState($this->sp)->create([
            'ibge_code' => '3509502',
            'name' => 'Campinas',
            'is_capital' => false,
        ]);
        City::factory()->forState($this->rj)->create([
            'ibge_code' => '3304557',
            'name' => 'Rio de Janeiro',
            'is_capital' => true,
        ]);
    }

    #[Test]
    public function it_lists_states_alphabetically_with_ibge_code(): void
    {
        $response = $this->getJson('/api/states');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertSame('Rio de Janeiro', $data[0]['name']);
        $this->assertSame('São Paulo', $data[1]['name']);
        $this->assertSame('35', $data[1]['ibge_code']);
        $this->assertSame('SP', $data[1]['uf']);
        $this->assertArrayNotHasKey('success', $data);
    }

    #[Test]
    public function it_lists_cities_by_state_id(): void
    {
        $response = $this->getJson("/api/states/{$this->sp->id}/cities");

        $response->assertOk();
        $payload = $response->json('data');
        $this->assertSame('SP', $payload['state']['uf']);
        $names = collect($payload['cities'])->pluck('name')->all();
        $this->assertSame(['Campinas', 'São Paulo'], $names);
        $this->assertSame('3509502', $payload['cities'][0]['ibge_code']);
    }

    #[Test]
    public function it_lists_cities_by_state_uf_alias(): void
    {
        $response = $this->getJson('/api/states/SP/cities');

        $response->assertOk();
        $payload = $response->json('data');
        $this->assertSame($this->sp->id, $payload['state']['id']);
        $this->assertCount(2, $payload['cities']);
    }

    #[Test]
    public function it_returns_404_for_unknown_state(): void
    {
        $this->getJson('/api/states/XX/cities')->assertStatus(404);
        $this->getJson('/api/states/999999/cities')->assertStatus(404);
    }

    #[Test]
    public function it_paginates_cities_list(): void
    {
        $response = $this->getJson('/api/cities?per_page=10');

        $response->assertOk();
        $this->assertIsArray($response->json('data'));
        $this->assertNotEmpty($response->json('data'));
        $this->assertTrue($response->json('success'));
        $this->assertArrayHasKey('meta', $response->json());
    }

    #[Test]
    public function state_has_many_cities_relationship(): void
    {
        $this->assertTrue($this->sp->cities()->where('name', 'Campinas')->exists());
        $this->assertTrue($this->campinas->state()->is($this->sp));
    }

    #[Test]
    public function it_resolves_city_from_cep_by_ibge_code(): void
    {
        $response = $this->getJson('/api/location/resolve-cep?' . http_build_query([
            'ibge' => '3550308',
            'uf' => 'SP',
            'city' => 'Sao Paulo',
        ]));

        $response->assertOk();
        $city = $response->json('data');
        $this->assertSame('São Paulo', $city['name']);
        $this->assertSame('3550308', $city['ibge_code']);
        $this->assertSame('SP', $city['state']['uf']);
        $this->assertArrayNotHasKey('success', $city);
    }

    #[Test]
    public function it_resolves_city_from_cep_by_normalized_name_fallback(): void
    {
        $response = $this->getJson('/api/location/resolve-cep?' . http_build_query([
            'uf' => 'SP',
            'city' => 'campinas',
        ]));

        $response->assertOk();
        $this->assertSame('Campinas', $response->json('data.name'));
    }

    #[Test]
    public function it_validates_resolve_cep_requires_ibge_or_uf_city(): void
    {
        $this->getJson('/api/location/resolve-cep')->assertStatus(422);
    }

    #[Test]
    public function location_service_caches_states_list(): void
    {
        /** @var LocationService $service */
        $service = app(LocationService::class);

        $first = $service->getAllStates();
        State::factory()->create([
            'ibge_code' => '31',
            'uf' => 'MG',
            'name' => 'Minas Gerais',
            'region' => 'Sudeste',
        ]);
        $second = $service->getAllStates();

        $this->assertCount($first->count(), $second);
        $this->assertTrue(Cache::has('location.states'));
    }
}
