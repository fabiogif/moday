<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class StateSeeder extends Seeder
{
     /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['uf' => 'AC', 'name' => 'Acre'],
            ['uf' => 'AL', 'name' => 'Alagoas'],
            ['uf' => 'AP', 'name' => 'Amapá'],
            ['uf' => 'AM', 'name' => 'Amazonas'],
            ['uf' => 'BA', 'name' => 'Bahia'],
            ['uf' => 'CE', 'name' => 'Ceará'],
            ['uf' => 'DF', 'name' => 'Distrito Federal'],
            ['uf' => 'ES', 'name' => 'Espírito Santo'],
            ['uf' => 'GO', 'name' => 'Goiás'],
            ['uf' => 'MA', 'name' => 'Maranhão'],
            ['uf' => 'MT', 'name' => 'Mato Grosso'],
            ['uf' => 'MS', 'name' => 'Mato Grosso do Sul'],
            ['uf' => 'MG', 'name' => 'Minas Gerais'],
            ['uf' => 'PA', 'name' => 'Pará'],
            ['uf' => 'PB', 'name' => 'Paraíba'],
            ['uf' => 'PR', 'name' => 'Paraná'],
            ['uf' => 'PE', 'name' => 'Pernambuco'],
            ['uf' => 'PI', 'name' => 'Piauí'],
            ['uf' => 'RJ', 'name' => 'Rio de Janeiro'],
            ['uf' => 'RN', 'name' => 'Rio Grande do Norte'],
            ['uf' => 'RS', 'name' => 'Rio Grande do Sul'],
            ['uf' => 'RO', 'name' => 'Rondônia'],
            ['uf' => 'RR', 'name' => 'Roraima'],
            ['uf' => 'SC', 'name' => 'Santa Catarina'],
            ['uf' => 'SP', 'name' => 'São Paulo'],
            ['uf' => 'SE', 'name' => 'Sergipe'],
            ['uf' => 'TO', 'name' => 'Tocantins'],
        ];

        foreach ($states as $state) {
            $state['created_at'] = now();
            $state['updated_at'] = now();
            DB::table('states')->insert($state);
        }
    }
}
