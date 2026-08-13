<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\State;
use Database\Seeders\StatesAndCitiesSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatesAndCitiesSeederTest extends TestCase
{
    #[Test]
    public function it_seeds_all_brazilian_states_and_municipalities_from_local_json(): void
    {
        $this->seed(StatesAndCitiesSeeder::class);

        $this->assertSame(27, State::query()->count());
        $this->assertGreaterThanOrEqual(5500, City::query()->count());

        $sp = State::query()->where('uf', 'SP')->first();
        $this->assertNotNull($sp);
        $this->assertSame('35', $sp->ibge_code);
        $this->assertSame('Sudeste', $sp->region);

        $saoPaulo = City::query()->where('ibge_code', '3550308')->first();
        $this->assertNotNull($saoPaulo);
        $this->assertSame('São Paulo', $saoPaulo->name);
        $this->assertTrue((bool) $saoPaulo->is_capital);
        $this->assertSame($sp->id, $saoPaulo->state_id);

        $ibgeCodes = City::query()->pluck('ibge_code');
        $this->assertSame($ibgeCodes->count(), $ibgeCodes->unique()->count());
    }
}
