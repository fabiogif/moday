<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CepLookupApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.viacep.base_url' => 'https://viacep.test']);
    }

    #[Test]
    public function retorna_endereco_e_cidade_local_resolvida_por_ibge(): void
    {
        $sp = State::factory()->create(['ibge_code' => '35', 'uf' => 'SP', 'name' => 'São Paulo']);
        City::factory()->forState($sp)->create(['ibge_code' => '3550308', 'name' => 'São Paulo']);

        Http::fake([
            'viacep.test/ws/01001000/json/' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'complemento' => 'lado ímpar',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'ibge' => '3550308',
            ], 200),
        ]);

        $response = $this->getJson('/api/cep/01001000');

        $response->assertOk();
        $response->assertJsonPath('data.address', 'Praça da Sé');
        $response->assertJsonPath('data.neighborhood', 'Sé');
        $response->assertJsonPath('data.zip_code', '01001-000');
        $response->assertJsonPath('data.city.name', 'São Paulo');
        $response->assertJsonPath('data.state.uf', 'SP');
    }

    #[Test]
    public function retorna_endereco_mesmo_sem_cidade_cadastrada_localmente(): void
    {
        Http::fake([
            'viacep.test/ws/*' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'ibge' => '3550308',
            ], 200),
        ]);

        $response = $this->getJson('/api/cep/01001000');

        $response->assertOk();
        $response->assertJsonPath('data.address', 'Praça da Sé');
        $response->assertJsonPath('data.city', null);
        $response->assertJsonPath('data.state', null);
    }

    #[Test]
    public function retorna_404_quando_viacep_nao_encontra_o_cep(): void
    {
        Http::fake([
            'viacep.test/ws/*' => Http::response(['erro' => true], 200),
        ]);

        $this->getJson('/api/cep/99999999')->assertStatus(404);
    }

    #[Test]
    public function retorna_404_quando_viacep_envia_erro_como_string(): void
    {
        Http::fake([
            'viacep.test/ws/*' => Http::response(['erro' => 'true'], 200),
        ]);

        $this->getJson('/api/cep/40325465')->assertStatus(404);
    }

    #[Test]
    public function retorna_404_quando_viacep_falha(): void
    {
        Http::fake([
            'viacep.test/ws/*' => Http::response(null, 500),
        ]);

        $this->getJson('/api/cep/01001000')->assertStatus(404);
    }

    #[Test]
    public function retorna_422_para_cep_com_formato_invalido(): void
    {
        Http::fake();

        $this->getJson('/api/cep/123')->assertStatus(422);
    }
}
