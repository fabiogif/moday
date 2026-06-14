<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = \App\Models\Tenant::first();

        $categories = [
            ['name' => 'Medicamentos',        'description' => 'Medicamentos e fármacos em geral'],
            ['name' => 'Alimentos',            'description' => 'Produtos alimentícios'],
            ['name' => 'Bebidas',              'description' => 'Bebidas e sucos'],
            ['name' => 'Higiene Pessoal',      'description' => 'Produtos de higiene e cuidados pessoais'],
            ['name' => 'Limpeza',              'description' => 'Produtos de limpeza doméstica e industrial'],
            ['name' => 'Cosméticos',           'description' => 'Cosméticos e perfumaria'],
            ['name' => 'Suplementos',          'description' => 'Suplementos alimentares e vitaminas'],
            ['name' => 'Materiais Hospitalares','description' => 'Materiais e equipamentos hospitalares'],
            ['name' => 'Veterinários',         'description' => 'Produtos veterinários'],
            ['name' => 'Outros',               'description' => 'Produtos diversos'],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                ['name' => $data['name'], 'tenant_id' => $tenant->id],
                [
                    'description' => $data['description'],
                    'status' => 'A',
                    'uuid' => Str::uuid(),
                    'tenant_id' => $tenant->id,
                ]
            );
        }

        $this->command->info('✅ Categorias de distribuidora criadas.');
    }
}
