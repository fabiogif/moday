<?php

namespace App\Services\Logistics;

use App\Models\Shipment;
use App\Models\Tenant;
use App\Exceptions\StockException;

class FreightWeightService
{
    public const CHARGE_MODE_PER_KG = 'per_kg';
    public const CHARGE_MODE_PER_CTE = 'per_cte';

    public const DEFAULT_SETTINGS = [
        'cf' => 0.0,
        'cv' => 0.0,
        'mkp' => 1.0,
        'charge_mode' => self::CHARGE_MODE_PER_KG,
    ];

    /**
     * @return array{cf: float, cv: float, mkp: float, charge_mode: string, fp_unit: float, unit_label: string}
     */
    public function getSettings(int $tenantId): array
    {
        $tenant = Tenant::find($tenantId);
        $stored = is_array($tenant?->settings['freight_weight'] ?? null)
            ? $tenant->settings['freight_weight']
            : [];

        $settings = [
            'cf' => (float) ($stored['cf'] ?? self::DEFAULT_SETTINGS['cf']),
            'cv' => (float) ($stored['cv'] ?? self::DEFAULT_SETTINGS['cv']),
            'mkp' => (float) ($stored['mkp'] ?? self::DEFAULT_SETTINGS['mkp']),
            'charge_mode' => $this->normalizeChargeMode($stored['charge_mode'] ?? self::DEFAULT_SETTINGS['charge_mode']),
        ];

        $fpUnit = $this->computeFpUnit($settings['cf'], $settings['cv'], $settings['mkp']);

        return array_merge($settings, [
            'fp_unit' => $fpUnit,
            'unit_label' => $settings['charge_mode'] === self::CHARGE_MODE_PER_CTE ? 'R$/CT-e' : 'R$/kg',
            'formula' => 'FP = (CF + CV) × MKP',
        ]);
    }

    /**
     * @param  array{cf?: mixed, cv?: mixed, mkp?: mixed, charge_mode?: mixed}  $input
     * @return array{cf: float, cv: float, mkp: float, charge_mode: string, fp_unit: float, unit_label: string, formula: string}
     */
    public function saveSettings(int $tenantId, array $input): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $settings = $tenant->settings ?? [];

        $payload = [
            'cf' => round((float) ($input['cf'] ?? 0), 4),
            'cv' => round((float) ($input['cv'] ?? 0), 4),
            'mkp' => round((float) ($input['mkp'] ?? 1), 4),
            'charge_mode' => $this->normalizeChargeMode($input['charge_mode'] ?? self::CHARGE_MODE_PER_KG),
        ];

        if ($payload['cf'] < 0 || $payload['cv'] < 0) {
            throw StockException::invalidMovement('CF e CV devem ser maiores ou iguais a zero.');
        }
        if ($payload['mkp'] <= 0) {
            throw StockException::invalidMovement('MKP deve ser maior que zero.');
        }

        $settings['freight_weight'] = $payload;
        $tenant->settings = $settings;
        $tenant->save();

        return $this->getSettings($tenantId);
    }

    /**
     * Calcula e persiste o Frete Peso do romaneio.
     *
     * FP = (CF + CV) × MKP  → tarifa unitária (R$/kg ou R$/CT-e)
     * Total = FP × quantidade (kg ou nº de conhecimentos)
     *
     * @param  array{cf?: float, cv?: float, mkp?: float, charge_mode?: string, cte_count?: float|int}  $overrides
     * @return array<string, mixed>
     */
    public function calculate(Shipment $shipment, array $overrides = []): array
    {
        $settings = $this->getSettings((int) $shipment->tenant_id);

        $cf = array_key_exists('cf', $overrides) ? (float) $overrides['cf'] : $settings['cf'];
        $cv = array_key_exists('cv', $overrides) ? (float) $overrides['cv'] : $settings['cv'];
        $mkp = array_key_exists('mkp', $overrides) ? (float) $overrides['mkp'] : $settings['mkp'];
        $chargeMode = $this->normalizeChargeMode(
            $overrides['charge_mode'] ?? $settings['charge_mode']
        );

        if ($cf < 0 || $cv < 0) {
            throw StockException::invalidMovement('CF e CV devem ser maiores ou iguais a zero.');
        }
        if ($mkp <= 0) {
            throw StockException::invalidMovement('MKP deve ser maior que zero.');
        }

        $fpUnit = $this->computeFpUnit($cf, $cv, $mkp);
        $quantity = $this->resolveQuantity($shipment, $chargeMode, $overrides);
        $total = round($fpUnit * $quantity, 2);

        $breakdown = [
            'formula' => 'FP = (CF + CV) × MKP',
            'cf' => round($cf, 4),
            'cv' => round($cv, 4),
            'mkp' => round($mkp, 4),
            'fp_unit' => $fpUnit,
            'charge_mode' => $chargeMode,
            'unit_label' => $chargeMode === self::CHARGE_MODE_PER_CTE ? 'R$/CT-e' : 'R$/kg',
            'quantity' => $quantity,
            'quantity_label' => $chargeMode === self::CHARGE_MODE_PER_CTE ? 'CT-e' : 'kg',
            'total' => $total,
        ];

        $shipment->update([
            'freight_weight_amount' => $total,
            'freight_weight_unit' => $fpUnit,
            'freight_weight_charge_mode' => $chargeMode,
            'freight_weight_quantity' => $quantity,
            'freight_weight_breakdown' => $breakdown,
        ]);

        return $breakdown;
    }

    private function computeFpUnit(float $cf, float $cv, float $mkp): float
    {
        return round(($cf + $cv) * $mkp, 4);
    }

    /**
     * @param  array{cte_count?: float|int}  $overrides
     */
    private function resolveQuantity(Shipment $shipment, string $chargeMode, array $overrides): float
    {
        if ($chargeMode === self::CHARGE_MODE_PER_CTE) {
            if (isset($overrides['cte_count'])) {
                $count = (float) $overrides['cte_count'];
            } else {
                $count = (float) $shipment->saleOrders()->count();
            }

            if ($count <= 0) {
                throw StockException::invalidMovement(
                    'Informe a quantidade de CT-e (conhecimentos) para calcular o frete peso.'
                );
            }

            return round($count, 3);
        }

        $weight = (float) ($shipment->total_weight_kg ?? 0);
        if ($weight <= 0) {
            throw StockException::invalidMovement(
                'Romaneio sem peso total. Cadastre o peso dos produtos para calcular o frete por quilo.'
            );
        }

        return round($weight, 3);
    }

    private function normalizeChargeMode(mixed $mode): string
    {
        $mode = is_string($mode) ? $mode : self::CHARGE_MODE_PER_KG;

        return in_array($mode, [self::CHARGE_MODE_PER_KG, self::CHARGE_MODE_PER_CTE], true)
            ? $mode
            : self::CHARGE_MODE_PER_KG;
    }
}
