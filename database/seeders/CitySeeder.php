<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class CitySeeder extends Seeder
{
 /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Busca os estados do banco de dados
        $states = DB::table('states')->get()->keyBy('uf');

        $cities = [
            // Bahia
            ['name' => 'Salvador', 'state_uf' => 'BA'],
            ['name' => 'Feira de Santana', 'state_uf' => 'BA'],
            ['name' => 'Vitória da Conquista', 'state_uf' => 'BA'],
            ['name' => 'Camaçari', 'state_uf' => 'BA'],
            ['name' => 'Juazeiro', 'state_uf' => 'BA'],
            ['name' => 'Lauro de Freitas', 'state_uf' => 'BA'],
            ['name' => 'Ilhéus', 'state_uf' => 'BA'],
            ['name' => 'Itabuna', 'state_uf' => 'BA'],
            ['name' => 'Alagoinhas', 'state_uf' => 'BA'],
            ['name' => 'Porto Seguro', 'state_uf' => 'BA'],
            
            // São Paulo
            ['name' => 'São Paulo', 'state_uf' => 'SP'],
            ['name' => 'Guarulhos', 'state_uf' => 'SP'],
            ['name' => 'Campinas', 'state_uf' => 'SP'],
            ['name' => 'São Bernardo do Campo', 'state_uf' => 'SP'],
            ['name' => 'Santo André', 'state_uf' => 'SP'],
            ['name' => 'Osasco', 'state_uf' => 'SP'],
            ['name' => 'São José dos Campos', 'state_uf' => 'SP'],
            ['name' => 'Ribeirão Preto', 'state_uf' => 'SP'],
            ['name' => 'Sorocaba', 'state_uf' => 'SP'],
            ['name' => 'Santos', 'state_uf' => 'SP'],
            
            // Rio de Janeiro
            ['name' => 'Rio de Janeiro', 'state_uf' => 'RJ'],
            ['name' => 'São Gonçalo', 'state_uf' => 'RJ'],
            ['name' => 'Duque de Caxias', 'state_uf' => 'RJ'],
            ['name' => 'Nova Iguaçu', 'state_uf' => 'RJ'],
            ['name' => 'Niterói', 'state_uf' => 'RJ'],
            ['name' => 'Belford Roxo', 'state_uf' => 'RJ'],
            ['name' => 'Campos dos Goytacazes', 'state_uf' => 'RJ'],
            ['name' => 'Petrópolis', 'state_uf' => 'RJ'],
            
            // Minas Gerais
            ['name' => 'Belo Horizonte', 'state_uf' => 'MG'],
            ['name' => 'Uberlândia', 'state_uf' => 'MG'],
            ['name' => 'Contagem', 'state_uf' => 'MG'],
            ['name' => 'Juiz de Fora', 'state_uf' => 'MG'],
            ['name' => 'Betim', 'state_uf' => 'MG'],
            ['name' => 'Montes Claros', 'state_uf' => 'MG'],
            
            // Paraná
            ['name' => 'Curitiba', 'state_uf' => 'PR'],
            ['name' => 'Londrina', 'state_uf' => 'PR'],
            ['name' => 'Maringá', 'state_uf' => 'PR'],
            ['name' => 'Ponta Grossa', 'state_uf' => 'PR'],
            ['name' => 'Cascavel', 'state_uf' => 'PR'],
            ['name' => 'Foz do Iguaçu', 'state_uf' => 'PR'],
            
            // Rio Grande do Sul
            ['name' => 'Porto Alegre', 'state_uf' => 'RS'],
            ['name' => 'Caxias do Sul', 'state_uf' => 'RS'],
            ['name' => 'Pelotas', 'state_uf' => 'RS'],
            ['name' => 'Canoas', 'state_uf' => 'RS'],
            ['name' => 'Santa Maria', 'state_uf' => 'RS'],
            
            // Ceará
            ['name' => 'Fortaleza', 'state_uf' => 'CE'],
            ['name' => 'Caucaia', 'state_uf' => 'CE'],
            ['name' => 'Juazeiro do Norte', 'state_uf' => 'CE'],
            ['name' => 'Maracanaú', 'state_uf' => 'CE'],
            
            // Pernambuco
            ['name' => 'Recife', 'state_uf' => 'PE'],
            ['name' => 'Jaboatão dos Guararapes', 'state_uf' => 'PE'],
            ['name' => 'Olinda', 'state_uf' => 'PE'],
            ['name' => 'Caruaru', 'state_uf' => 'PE'],
            ['name' => 'Petrolina', 'state_uf' => 'PE'],
            
            // Santa Catarina
            ['name' => 'Florianópolis', 'state_uf' => 'SC'],
            ['name' => 'Joinville', 'state_uf' => 'SC'],
            ['name' => 'Blumenau', 'state_uf' => 'SC'],
            ['name' => 'São José', 'state_uf' => 'SC'],
            
            // Goiás
            ['name' => 'Goiânia', 'state_uf' => 'GO'],
            ['name' => 'Aparecida de Goiânia', 'state_uf' => 'GO'],
            ['name' => 'Anápolis', 'state_uf' => 'GO'],
            
            // Pará
            ['name' => 'Belém', 'state_uf' => 'PA'],
            ['name' => 'Ananindeua', 'state_uf' => 'PA'],
            ['name' => 'Santarém', 'state_uf' => 'PA'],
            
            // Amazonas
            ['name' => 'Manaus', 'state_uf' => 'AM'],
            
            // Distrito Federal
            ['name' => 'Brasília', 'state_uf' => 'DF'],
        ];

        foreach ($cities as $city) {
            if (isset($states[$city['state_uf']])) {
                DB::table('cities')->insert([
                    'name' => $city['name'],
                    'state_id' => $states[$city['state_uf']]->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
