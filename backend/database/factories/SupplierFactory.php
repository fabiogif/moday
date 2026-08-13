<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $documentType = $this->faker->randomElement(['cpf', 'cnpj']);
        
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->company(),
            'fantasy_name' => $this->faker->companySuffix() . ' ' . $this->faker->word(),
            'document' => $documentType === 'cnpj' 
                ? $this->faker->numerify('##############') // 14 dígitos
                : $this->faker->numerify('###########'), // 11 dígitos
            'document_type' => $documentType,
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->numerify('###########'),
            'phone2' => $this->faker->optional()->numerify('###########'),
            'address' => $this->faker->streetAddress(),
            'number' => $this->faker->buildingNumber(),
            'complement' => $this->faker->optional()->secondaryAddress(),
            'neighborhood' => $this->faker->cityPrefix(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip_code' => $this->faker->numerify('#####-###'),
            'bank_name' => $this->faker->optional()->randomElement(['Banco do Brasil', 'Itaú', 'Bradesco', 'Caixa']),
            'bank_agency' => $this->faker->optional()->numerify('####'),
            'bank_account' => $this->faker->optional()->numerify('#####-#'),
            'pix_key' => $this->faker->optional()->email(),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90), // 90% ativos
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCnpj(): self
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'cnpj',
            'document' => $this->faker->numerify('##############'),
        ]);
    }

    public function withCpf(): self
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'cpf',
            'document' => $this->faker->numerify('###########'),
        ]);
    }
}

