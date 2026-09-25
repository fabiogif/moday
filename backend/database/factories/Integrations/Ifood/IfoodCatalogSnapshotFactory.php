<?php

namespace Database\Factories\Integrations\Ifood;

use App\Models\Integrations\Ifood\IfoodCatalogSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class IfoodCatalogSnapshotFactory extends Factory
{
    protected $model = IfoodCatalogSnapshot::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'merchant_id' => $this->faker->uuid(),
            'catalog_id' => $this->faker->uuid(),
            'group_id' => $this->faker->uuid(),
            'snapshot_type' => $this->faker->randomElement([
                'catalogs',
                'categories',
                'catalog_groups',
                'unsellable_items',
                'sellable_items',
                'catalog_version',
            ]),
            'payload' => [
                'example' => $this->faker->sentence(),
            ],
            'captured_at' => now(),
        ];
    }
}

