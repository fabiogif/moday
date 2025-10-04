<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            [
                'identify' => 'MESA-001',
                'name' => 'Mesa Principal',
                'description' => 'Mesa principal do restaurante',
                'capacity' => 4,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-002',
                'name' => 'Mesa do Canto',
                'description' => 'Mesa aconchegante no canto',
                'capacity' => 2,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-003',
                'name' => 'Mesa Família',
                'description' => 'Mesa para famílias grandes',
                'capacity' => 8,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-004',
                'name' => 'Mesa VIP',
                'description' => 'Mesa VIP com vista privilegiada',
                'capacity' => 6,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-005',
                'name' => 'Mesa Dupla',
                'description' => 'Mesa para casais',
                'capacity' => 2,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-006',
                'name' => 'Mesa Central',
                'description' => 'Mesa no centro do salão',
                'capacity' => 4,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-007',
                'name' => 'Mesa Perto da Janela',
                'description' => 'Mesa com vista para a rua',
                'capacity' => 4,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-008',
                'name' => 'Mesa Grande',
                'description' => 'Mesa para grupos grandes',
                'capacity' => 10,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-009',
                'name' => 'Mesa Individual',
                'description' => 'Mesa para uma pessoa',
                'capacity' => 1,
                'is_active' => true,
            ],
            [
                'identify' => 'MESA-010',
                'name' => 'Mesa Inativa',
                'description' => 'Esta mesa está temporariamente inativa',
                'capacity' => 4,
                'is_active' => false,
            ],
        ];

        foreach ($tables as $tableData) {
            Table::create([
                'identify' => $tableData['identify'],
                'name' => $tableData['name'],
                'description' => $tableData['description'],
                'capacity' => $tableData['capacity'],
                'is_active' => $tableData['is_active'],
                'tenant_id' => 1, // Assumindo que existe um tenant com ID 1
            ]);
        }
    }
}
