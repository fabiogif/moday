<?php

namespace Database\Factories;

use App\Models\BankAccountLog;
use App\Models\TenantBankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountLogFactory extends Factory
{
    protected $model = BankAccountLog::class;

    public function definition(): array
    {
        $actions = ['created', 'updated', 'deleted', 'set_primary', 'verified'];

        return [
            'bank_account_id' => TenantBankAccount::factory(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement($actions),
            'old_values' => $this->faker->optional()->passthrough([
                'account_type' => 'checking',
                'is_active' => true,
            ]),
            'new_values' => $this->faker->optional()->passthrough([
                'account_type' => 'savings',
                'is_active' => false,
            ]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}

