<?php

namespace App\Services\Barcode\Clients;

use App\Services\Barcode\DTO\BarcodeProductData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CosmosBarcodeClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.cosmos.token'));
    }

    public function lookup(string $barcode): ?BarcodeProductData
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.cosmos.base_url', 'https://api.cosmos.bluesoft.com.br'), '/');
        $timeout = (int) config('services.cosmos.timeout', 2);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders([
                    'X-Cosmos-Token' => config('services.cosmos.token'),
                ])
                ->get("{$baseUrl}/gtins/{$barcode}");

            if ($response->status() === 404) {
                return null;
            }

            if (!$response->successful()) {
                Log::error('Cosmos barcode lookup failed', [
                    'barcode' => $barcode,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $json = $response->json() ?? [];
            $description = $json['description'] ?? $json['description_type'] ?? null;
            $brand = data_get($json, 'brand.name') ?? $json['brand'] ?? null;
            $category = data_get($json, 'gpc.description')
                ?? data_get($json, 'ncm.description')
                ?? null;
            $image = data_get($json, 'thumbnail')
                ?? data_get($json, 'avg_price.thumbnail')
                ?? null;

            $weight = $this->extractNumeric($json['net_weight'] ?? $json['gross_weight'] ?? null);
            $volume = null;

            if (isset($json['net_content'])) {
                $parsed = $this->parseQuantity((string) $json['net_content']);
                $weight = $parsed['weight'] ?? $weight;
                $volume = $parsed['volume'] ?? $volume;
            }

            $data = new BarcodeProductData(
                barcode: $barcode,
                name: is_string($description) ? $description : null,
                brand: is_string($brand) ? $brand : null,
                category: is_string($category) ? $category : null,
                unitOfMeasure: isset($json['unit']) && is_string($json['unit']) ? $json['unit'] : null,
                weight: $weight,
                volume: $volume,
                imageUrl: is_string($image) ? $image : null,
                description: is_string($description) ? $description : null,
                source: 'cosmos',
                raw: $json,
            );

            return $data->hasUsableData() ? $data : null;
        } catch (\Throwable $ex) {
            Log::error('Cosmos barcode lookup exception', [
                'barcode' => $barcode,
                'error' => $ex->getMessage(),
            ]);

            return null;
        }
    }

    private function extractNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @return array{weight?: float, volume?: float}
     */
    private function parseQuantity(string $raw): array
    {
        if (!preg_match('/([\d.,]+)\s*(kg|g|l|ml|lt)?/i', $raw, $m)) {
            return [];
        }

        $amount = (float) str_replace(',', '.', $m[1]);
        $unit = strtolower($m[2] ?? '');

        return match ($unit) {
            'kg' => ['weight' => $amount],
            'g' => ['weight' => $amount / 1000],
            'l', 'lt' => ['volume' => $amount],
            'ml' => ['volume' => $amount / 1000],
            default => ['weight' => $amount],
        };
    }
}
