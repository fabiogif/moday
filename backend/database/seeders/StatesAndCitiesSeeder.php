<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StatesAndCitiesSeeder extends Seeder
{
    public function run(): void
    {
        $statesPath = database_path('data/states.json');
        $citiesPath = database_path('data/cities.json');

        if (!File::exists($statesPath) || !File::exists($citiesPath)) {
            $this->command?->error('Arquivos database/data/states.json e cities.json são obrigatórios.');
            return;
        }

        $statesData = json_decode(File::get($statesPath), true, 512, JSON_THROW_ON_ERROR);
        $citiesData = json_decode(File::get($citiesPath), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($statesData) || !is_array($citiesData)) {
            $this->command?->error('Formato inválido nos arquivos JSON de localização.');
            return;
        }

        $this->command?->info('Importando Estados e Municípios (base IBGE local)...');

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        City::query()->delete();
        State::query()->delete();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        DB::transaction(function () use ($statesData, $citiesData) {
            $now = now();
            $statesByUf = [];

            $stateRows = [];
            foreach ($statesData as $estado) {
                $stateRows[] = [
                    'ibge_code' => (string) $estado['ibge_code'],
                    'uf' => strtoupper((string) $estado['uf']),
                    'name' => (string) $estado['name'],
                    'region' => (string) ($estado['region'] ?? ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            State::insert($stateRows);

            foreach (State::query()->get(['id', 'uf']) as $state) {
                $statesByUf[$state->uf] = $state->id;
            }

            $this->command?->info('✓ ' . count($statesByUf) . ' estados inseridos');

            $batchSize = 500;
            $inserted = 0;
            $batch = [];

            foreach ($citiesData as $cidade) {
                $uf = strtoupper((string) $cidade['state_uf']);
                if (!isset($statesByUf[$uf])) {
                    continue;
                }

                $batch[] = [
                    'state_id' => $statesByUf[$uf],
                    'ibge_code' => (string) $cidade['ibge_code'],
                    'name' => (string) $cidade['name'],
                    'is_capital' => (bool) ($cidade['is_capital'] ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    City::insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                    $this->command?->info("Inseridas {$inserted} / " . count($citiesData) . ' cidades...');
                }
            }

            if ($batch !== []) {
                City::insert($batch);
                $inserted += count($batch);
            }

            $this->command?->info("✓ {$inserted} cidades inseridas");
        });

        Cache::forget('location.states');
        foreach (State::query()->pluck('id') as $stateId) {
            Cache::forget("location.cities.{$stateId}");
        }

        $this->command?->info('✓ Importação IBGE concluída (cache invalidado)');
    }
}
