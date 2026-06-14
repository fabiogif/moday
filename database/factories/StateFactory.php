<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\State>
 */
class StateFactory extends Factory
{
    protected $model = State::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = [
            ['uf' => 'AC', 'name' => 'Acre'],
            ['uf' => 'AL', 'name' => 'Alagoas'],
            ['uf' => 'AP', 'name' => 'Amapá'],
            ['uf' => 'AM', 'name' => 'Amazonas'],
            ['uf' => 'BA', 'name' => 'Bahia'],
            ['uf' => 'CE', 'name' => 'Ceará'],
            ['uf' => 'DF', 'name' => 'Distrito Federal'],
            ['uf' => 'ES', 'name' => 'Espírito Santo'],
            ['uf' => 'GO', 'name' => 'Goiás'],
            ['uf' => 'MA', 'name' => 'Maranhão'],
            ['uf' => 'MT', 'name' => 'Mato Grosso'],
            ['uf' => 'MS', 'name' => 'Mato Grosso do Sul'],
            ['uf' => 'MG', 'name' => 'Minas Gerais'],
            ['uf' => 'PA', 'name' => 'Pará'],
            ['uf' => 'PB', 'name' => 'Paraíba'],
            ['uf' => 'PR', 'name' => 'Paraná'],
            ['uf' => 'PE', 'name' => 'Pernambuco'],
            ['uf' => 'PI', 'name' => 'Piauí'],
            ['uf' => 'RJ', 'name' => 'Rio de Janeiro'],
            ['uf' => 'RN', 'name' => 'Rio Grande do Norte'],
            ['uf' => 'RS', 'name' => 'Rio Grande do Sul'],
            ['uf' => 'RO', 'name' => 'Rondônia'],
            ['uf' => 'RR', 'name' => 'Roraima'],
            ['uf' => 'SC', 'name' => 'Santa Catarina'],
            ['uf' => 'SP', 'name' => 'São Paulo'],
            ['uf' => 'SE', 'name' => 'Sergipe'],
            ['uf' => 'TO', 'name' => 'Tocantins'],
        ];

        $state = $this->faker->randomElement($states);

        return [
            'uf' => $state['uf'],
            'name' => $state['name'],
        ];
    }

    /**
     * Create a specific state by UF
     */
    public function withUf(string $uf): static
    {
        $statesMap = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];

        return $this->state(fn (array $attributes) => [
            'uf' => $uf,
            'name' => $statesMap[$uf] ?? $uf,
        ]);
    }
}

