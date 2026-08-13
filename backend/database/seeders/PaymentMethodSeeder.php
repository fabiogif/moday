<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethod= [
            [
                'name' => 'PIX',
                'description' => 'PIX',
                'is_active' => true,
            ],
            [
                'name' => 'Cartão de Crédito',
                'description' => 'Cartão de Crédito',
                'is_active' => true,
            ],
            [
                'name' => 'Cartão de Débito',
                'description' => 'Cartão de Débito',
                'is_active' => true,
            ],
            [
                'name' => 'Dinheiro',
                'description' => 'Dinheiro',
                'is_active' => true,
            ],
            [
                'name' => 'Ticket',
                'description' => 'Ticket',
                'is_active' => true,
            ],
            [
                'name' => 'Voucher',
                'description' => 'Voucher',
                'is_active' => true,
            ]
        ];

        foreach ($paymentMethod as $paymentMethodData) {
            PaymentMethod::create([
                'name' => $paymentMethodData['name'],
                'description' => $paymentMethodData['description'],
                'is_active' => $paymentMethodData['is_active'],
                'uuid' => Str::uuid(),
                'tenant_id' => 1, // Assumindo que existe um tenant com ID 1
            ]);
        }
    }
}
