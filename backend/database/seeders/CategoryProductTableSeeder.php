<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryProductTableSeeder extends Seeder
{

    public function run(): void
    {
        Category::create([
            'name' =>  fake()->unique()->name(),
            'description'=> fake()->sentence(),
            'tenant_id' => 1,
        ]);
    }
}
