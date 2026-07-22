<?php

namespace App\Services\Barcode;

use App\Models\BarcodeLookup;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Barcode\Clients\CosmosBarcodeClient;
use App\Services\Barcode\Clients\OpenFoodFactsBarcodeClient;
use App\Services\Barcode\DTO\BarcodeProductData;
use InvalidArgumentException;

class BarcodeLookupService
{
    public const STATUS_EXISTING = 'existing_product';
    public const STATUS_FOUND = 'found';
    public const STATUS_NOT_FOUND = 'not_found';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public function __construct(
        private readonly BarcodeValidator $validator,
        private readonly ProductRepository $productRepository,
        private readonly CosmosBarcodeClient $cosmosClient,
        private readonly OpenFoodFactsBarcodeClient $openFoodFactsClient,
    ) {}

    /**
     * Fluxo: valida → produto local → cache → Cosmos → Open Food Facts.
     *
     * @return array{
     *   status: string,
     *   barcode: string|null,
     *   message: string,
     *   source: string|null,
     *   product: array<string, mixed>|null,
     *   suggestion: array<string, mixed>|null
     * }
     */
    public function lookup(?string $rawCode, int $tenantId): array
    {
        try {
            $barcode = $this->validator->assertValid($rawCode);
        } catch (InvalidArgumentException $ex) {
            return [
                'status' => self::STATUS_INVALID,
                'barcode' => $rawCode,
                'message' => $ex->getMessage(),
                'source' => null,
                'product' => null,
                'suggestion' => null,
            ];
        }

        $existing = $this->productRepository->findByCode($barcode, $tenantId, catalogOnly: false);
        if ($existing) {
            return [
                'status' => self::STATUS_EXISTING,
                'barcode' => $barcode,
                'message' => 'Produto já cadastrado.',
                'source' => 'local_product',
                'product' => $this->formatExistingProduct($existing),
                'suggestion' => null,
            ];
        }

        $cached = BarcodeLookup::query()->where('barcode', $barcode)->first();
        if ($cached) {
            return [
                'status' => self::STATUS_FOUND,
                'barcode' => $barcode,
                'message' => 'Produto encontrado.',
                'source' => 'cache',
                'product' => null,
                'suggestion' => $this->formatCacheSuggestion($cached),
            ];
        }

        $external = $this->lookupExternal($barcode);
        if ($external === null) {
            return [
                'status' => self::STATUS_NOT_FOUND,
                'barcode' => $barcode,
                'message' => 'Produto não encontrado nas bases externas.',
                'source' => null,
                'product' => null,
                'suggestion' => null,
            ];
        }

        $this->storeCache($external);

        return [
            'status' => self::STATUS_FOUND,
            'barcode' => $barcode,
            'message' => 'Produto encontrado.',
            'source' => $external->source,
            'product' => null,
            'suggestion' => $external->toArray(),
        ];
    }

    private function lookupExternal(string $barcode): ?BarcodeProductData
    {
        $fromCosmos = $this->cosmosClient->lookup($barcode);
        if ($fromCosmos !== null) {
            return $fromCosmos;
        }

        return $this->openFoodFactsClient->lookup($barcode);
    }

    private function storeCache(BarcodeProductData $data): void
    {
        BarcodeLookup::query()->updateOrCreate(
            ['barcode' => $data->barcode],
            [
                'source' => $data->source,
                'name' => $data->name,
                'brand' => $data->brand,
                'category' => $data->category,
                'unit_of_measure' => $data->unitOfMeasure,
                'weight' => $data->weight,
                'volume' => $data->volume,
                'image_url' => $data->imageUrl,
                'raw_payload' => $data->raw,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatExistingProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'uuid' => $product->uuid,
            'identify' => $product->uuid,
            'name' => $product->name,
            'description' => $product->description,
            'brand' => $product->brand,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'weight' => $product->weight !== null ? (float) $product->weight : null,
            'volume' => $product->volume !== null ? (float) $product->volume : null,
            'image_url' => $product->image_url,
            'unit_of_measure' => $product->unit_of_measure,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCacheSuggestion(BarcodeLookup $cached): array
    {
        return [
            'barcode' => $cached->barcode,
            'name' => $cached->name,
            'brand' => $cached->brand,
            'category' => $cached->category,
            'unit_of_measure' => $cached->unit_of_measure,
            'weight' => $cached->weight !== null ? (float) $cached->weight : null,
            'volume' => $cached->volume !== null ? (float) $cached->volume : null,
            'image_url' => $cached->image_url,
            'description' => $cached->name,
            'source' => 'cache',
        ];
    }
}
