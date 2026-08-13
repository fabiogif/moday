<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\JobPositionService;
use Illuminate\Database\Seeder;

class JobPositionSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(JobPositionService::class);

        Tenant::query()->pluck('id')->each(function (int $tenantId) use ($service) {
            $service->seedDefaultsForTenant($tenantId);
        });
    }
}
