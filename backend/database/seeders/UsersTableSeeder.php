<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\{Tenant, User};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        $tenant->users()->create([
            'name' => 'Fabio',
            'email' => 'fabio@fabio.com',
            'password' => bcrypt('123456'),
       ]);
    }
}
