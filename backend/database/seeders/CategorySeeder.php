<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Bebidas',
                'description' => 'Refrigerantes, sucos, água e outras bebidas',
                'is_active' => true,
            ],
            [
                'name' => 'Pratos Principais',
                'description' => 'Pratos principais do cardápio',
                'is_active' => true,
            ],
            [
                'name' => 'Sobremesas',
                'description' => 'Doces, sorvetes e sobremesas',
                'is_active' => true,
            ],
            [
                'name' => 'Aperitivos',
                'description' => 'Petiscos e aperitivos',
                'is_active' => true,
            ],
            [
                'name' => 'Saladas',
                'description' => 'Saladas frescas e variadas',
                'is_active' => true,
            ],
            [
                'name' => 'Massas',
                'description' => 'Pratos de massa italiana',
                'is_active' => true,
            ],
            [
                'name' => 'Carnes',
                'description' => 'Carnes grelhadas e assadas',
                'is_active' => true,
            ],
            [
                'name' => 'Vegetariano',
                'description' => 'Pratos vegetarianos e veganos',
                'is_active' => true,
            ],
            [
                'name' => 'Especiais',
                'description' => 'Pratos especiais do chef',
                'is_active' => true,
            ],
            [
                'name' => 'Categoria Inativa',
                'description' => 'Esta categoria está inativa',
                'is_active' => false,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'is_active' => $categoryData['is_active'],
                'uuid' => Str::uuid(),
                'tenant_id' => 1, // Assumindo que existe um tenant com ID 1
            ]);
        }
    }
}
