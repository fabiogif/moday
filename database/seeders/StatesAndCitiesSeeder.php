<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StatesAndCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando importação de Estados e Cidades...');

        // Ler arquivo db.json
        $jsonPath = base_path('db.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error('Arquivo db.json não encontrado!');
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['estados']) || !isset($data['cidades'])) {
            $this->command->error('Formato do arquivo db.json inválido!');
            return;
        }

        try {
            // Limpar tabelas antes de popular
            $this->command->info('Limpando tabelas...');
            
            // Desabilitar foreign key checks de forma compatível com o DB
            $driver = DB::getDriverName();
            
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } elseif ($driver === 'pgsql') {
                DB::statement('SET CONSTRAINTS ALL DEFERRED;');
            }
            
            City::truncate();
            State::truncate();
            
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            DB::beginTransaction();

            // Inserir Estados
            $this->command->info('Inserindo estados...');
            $estados = [];
            foreach ($data['estados'] as $estado) {
                $estados[$estado['id']] = State::create([
                    'uf' => $estado['id'],
                    'name' => $estado['estado'],
                ]);
            }
            $this->command->info('✓ ' . count($estados) . ' estados inseridos');

            // Inserir Cidades em lotes para melhor performance
            $this->command->info('Inserindo cidades...');
            $cidades = $data['cidades'];
            $totalCidades = count($cidades);
            $batchSize = 500;
            $inserted = 0;

            for ($i = 0; $i < $totalCidades; $i += $batchSize) {
                $batch = array_slice($cidades, $i, $batchSize);
                $cidadesData = [];

                foreach ($batch as $cidade) {
                    if (!isset($estados[$cidade['estadoId']])) {
                        continue; // Skip if state not found
                    }

                    $cidadesData[] = [
                        'state_id' => $estados[$cidade['estadoId']]->id,
                        'name' => $cidade['cidade'],
                        'is_capital' => isset($cidade['capital']) ? $cidade['capital'] : false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($cidadesData)) {
                    City::insert($cidadesData);
                    $inserted += count($cidadesData);
                    $this->command->info("Inseridas $inserted / $totalCidades cidades...");
                }
            }

            DB::commit();
            
            $this->command->info('✓ Importação concluída com sucesso!');
            $this->command->info("Total: " . count($estados) . " estados e $inserted cidades");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Erro na importação: ' . $e->getMessage());
            throw $e;
        }
    }
}

