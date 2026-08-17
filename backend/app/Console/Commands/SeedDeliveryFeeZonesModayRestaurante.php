<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class SeedDeliveryFeeZonesModayRestaurante extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-delivery-fee-zones-moday-restaurante';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grava as faixas de taxa de entrega por km (Delivery e Retirada) do tenant modayrestaurante@gmail.com';

    private const TENANT_EMAIL = 'modayrestaurante@gmail.com';

    /**
     * Zonas fornecidas pela Fran (WhatsApp, 17/08/2026):
     * total = base_fee + per_km_fee * km
     */
    private const ZONES = [
        ['name' => 'Zona 1', 'min_km' => 1, 'max_km' => 2, 'base_fee' => 0.00, 'per_km_fee' => 2.50],
        ['name' => 'Zona 2', 'min_km' => 3, 'max_km' => 5, 'base_fee' => 7.00, 'per_km_fee' => 1.00],
        ['name' => 'Zona 3', 'min_km' => 6, 'max_km' => 8, 'base_fee' => 10.00, 'per_km_fee' => 1.50],
        ['name' => 'Zona 4', 'min_km' => 9, 'max_km' => 12, 'base_fee' => 15.00, 'per_km_fee' => 2.00],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenant = Tenant::where('email', self::TENANT_EMAIL)->first();

        if (!$tenant) {
            $this->error("Tenant não encontrado para o e-mail " . self::TENANT_EMAIL);
            return self::FAILURE;
        }

        $settings = $tenant->settings ?? [];
        $settings['delivery_pickup'] = $settings['delivery_pickup'] ?? [];
        $settings['delivery_pickup']['delivery_fee_zones'] = self::ZONES;

        $tenant->settings = $settings;
        $tenant->save();

        $this->info("Zonas de taxa de entrega salvas para \"{$tenant->name}\" (tenant #{$tenant->id}):");
        $this->table(
            ['Zona', 'Km', 'Base', 'Por km', 'Exemplo (km máx.)'],
            collect(self::ZONES)->map(function (array $zone) {
                $maxKm = $zone['max_km'];
                $exemplo = number_format($zone['base_fee'] + $zone['per_km_fee'] * $maxKm, 2, ',', '.');
                $faixa = $zone['min_km'] === $zone['max_km']
                    ? "{$zone['min_km']} km"
                    : "{$zone['min_km']}–{$zone['max_km']} km";

                return [
                    $zone['name'],
                    $faixa,
                    'R$ ' . number_format($zone['base_fee'], 2, ',', '.'),
                    'R$ ' . number_format($zone['per_km_fee'], 2, ',', '.'),
                    "R$ {$exemplo} ({$maxKm} km)",
                ];
            })->all()
        );

        return self::SUCCESS;
    }
}
