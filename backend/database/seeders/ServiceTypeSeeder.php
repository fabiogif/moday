<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultTypes = [
            [
                'name' => 'Balcão',
                'slug' => 'counter',
                'identify' => 'counter',
                'description' => 'Atendimento no balcão do estabelecimento',
                'is_active' => true,
                'requires_address' => false,
                'requires_table' => false,
                'available_in_menu' => false,
                'order_position' => 1,
            ],
            [
                'name' => 'Mesa',
                'slug' => 'table',
                'identify' => 'table',
                'description' => 'Atendimento em mesa do estabelecimento',
                'is_active' => true,
                'requires_address' => false,
                'requires_table' => true,
                'available_in_menu' => false,
                'order_position' => 2,
            ],
            [
                'name' => 'Delivery',
                'slug' => 'delivery',
                'identify' => 'delivery',
                'description' => 'Entrega no endereço do cliente',
                'is_active' => true,
                'requires_address' => true,
                'requires_table' => false,
                'available_in_menu' => true,
                'order_position' => 3,
            ],
            [
                'name' => 'Retirada',
                'slug' => 'pickup',
                'identify' => 'pickup',
                'description' => 'Cliente retira no estabelecimento',
                'is_active' => true,
                'requires_address' => false,
                'requires_table' => false,
                'available_in_menu' => true,
                'order_position' => 4,
            ],
        ];

        foreach ($defaultTypes as $type) {
            ServiceType::updateOrCreate(
                ['identify' => $type['identify']],
                $type
            );
        }
    }
}













