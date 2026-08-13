<?php

namespace Database\Factories;

use App\Models\TenantBankAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantBankAccountFactory extends Factory
{
    protected $model = TenantBankAccount::class;

    public function definition(): array
    {
        $accountTypes = ['checking', 'savings', 'payment'];
        $banks = [
            ['code' => '001', 'name' => 'Banco do Brasil'],
            ['code' => '237', 'name' => 'Bradesco'],
            ['code' => '341', 'name' => 'Itaú'],
            ['code' => '104', 'name' => 'Caixa'],
            ['code' => '260', 'name' => 'Nubank'],
        ];
        
        $bank = $this->faker->randomElement($banks);
        $holderType = $this->faker->randomElement(['individual', 'company']);

        return [
            'tenant_id' => Tenant::factory(),
            'account_type' => $this->faker->randomElement($accountTypes),
            'bank_code' => $bank['code'],
            'bank_name' => $bank['name'],
            'agency' => $this->faker->numerify('####'),
            'agency_digit' => $this->faker->optional()->numerify('#'),
            'account_number' => $this->faker->numerify('########'),
            'account_digit' => $this->faker->numerify('#'),
            'account_holder_name' => $holderType === 'individual' 
                ? $this->faker->name() 
                : $this->faker->company(),
            'account_holder_document' => $holderType === 'individual'
                ? $this->faker->numerify('###########') // CPF
                : $this->faker->numerify('##############'), // CNPJ
            'account_holder_type' => $holderType,
            'pix_key_type' => $this->faker->optional()->randomElement(['cpf', 'cnpj', 'email', 'phone', 'random']),
            'pix_key' => $this->faker->optional()->email(),
            'is_primary' => false,
            'is_active' => true,
            'is_verified' => $this->faker->boolean(70), // 70% verificadas
            'verified_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'verification_method' => $this->faker->optional(0.7)->randomElement(['manual', 'api', 'document']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indica que a conta é principal
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Indica que a conta está inativa
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indica que a conta não está verificada
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
            'verification_method' => null,
        ]);
    }

    /**
     * Define tenant específico
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}

