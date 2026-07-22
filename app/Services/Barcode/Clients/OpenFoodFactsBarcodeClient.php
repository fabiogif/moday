<?php

namespace App\Services\Barcode\Clients;

use App\Services\Barcode\DTO\BarcodeProductData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenFoodFactsBarcodeClient
{
    public function lookup(string $barcode): ?BarcodeProductData
    {
        $baseUrl = rtrim((string) config(
            'services.open_food_facts.base_url',
            'https://world.openfoodfacts.org'
        ), '/');
        $timeout = (int) config('services.open_food_facts.timeout', 3);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => config('services.open_food_facts.user_agent', 'DistribTec/1.0 (barcode-lookup)'),
                ])
                ->get("{$baseUrl}/api/v2/product/{$barcode}.json");

            if ($response->status() === 404) {
                return null;
            }

            if (!$response->successful()) {
                Log::error('Open Food Facts barcode lookup failed', [
                    'barcode' => $barcode,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $json = $response->json() ?? [];
            if ((int) ($json['status'] ?? 0) !== 1) {
                return null;
            }

            $product = $json['product'] ?? [];
            if (!is_array($product) || $product === []) {
                return null;
            }

            $name = $product['product_name_pt']
                ?? $product['product_name']
                ?? $product['generic_name_pt']
                ?? $product['generic_name']
                ?? null;

            $brand = $product['brands'] ?? null;
            if (is_string($brand) && str_contains($brand, ',')) {
                $brand = trim(explode(',', $brand)[0]);
            }

            $category = null;
            if (!empty($product['categories_tags']) && is_array($product['categories_tags'])) {
                $tag = (string) ($product['categories_tags'][0] ?? '');
                $category = str_replace(['en:', 'pt:', '-'], ['', '', ' '], $tag);
                $category = ucwords(trim($category));
            } elseif (!empty($product['categories']) && is_string($product['categories'])) {
                $category = trim(explode(',', $product['categories'])[0]);
            }

            $image = $product['image_front_url']
                ?? $product['image_url']
                ?? $product['image_small_url']
                ?? null;

            $quantity = is_string($product['quantity'] ?? null) ? $product['quantity'] : '';
            $parsed = $this->parseQuantity($quantity);

            $data = new BarcodeProductData(
                barcode: $barcode,
                name: is_string($name) && $name !== '' ? $name : null,
                brand: is_string($brand) && $brand !== '' ? $brand : null,
                category: is_string($category) && $category !== '' ? $category : null,
                unitOfMeasure: $parsed['unit'] ?? null,
                weight: $parsed['weight'] ?? null,
                volume: $parsed['volume'] ?? null,
                imageUrl: is_string($image) ? $image : null,
                description: is_string($name) ? $name : null,
                source: 'open_food_facts',
                raw: $product,
            );

            return $data->hasUsableData() ? $data : null;
        } catch (\Throwable $ex) {
            Log::error('Open Food Facts barcode lookup exception', [
                'barcode' => $barcode,
                'error' => $ex->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{weight?: float, volume?: float, unit?: string}
     */
    private function parseQuantity(string $raw): array
    {
        if ($raw === '' || !preg_match('/([\d.,]+)\s*(kg|g|l|ml|lt)?/i', $raw, $m)) {
            return [];
        }

        $amount = (float) str_replace(',', '.', $m[1]);
        $unit = strtolower($m[2] ?? '');

        return match ($unit) {
            'kg' => ['weight' => $amount, 'unit' => 'KG'],
            'g' => ['weight' => $amount / 1000, 'unit' => 'KG'],
            'l', 'lt' => ['volume' => $amount, 'unit' => 'L'],
            'ml' => ['volume' => $amount / 1000, 'unit' => 'L'],
            default => ['weight' => $amount, 'unit' => 'UN'],
        };
    }
}
