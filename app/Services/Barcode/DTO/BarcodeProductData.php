<?php

namespace App\Services\Barcode\DTO;

final class BarcodeProductData
{
    public function __construct(
        public readonly string $barcode,
        public readonly ?string $name = null,
        public readonly ?string $brand = null,
        public readonly ?string $category = null,
        public readonly ?string $unitOfMeasure = null,
        public readonly ?float $weight = null,
        public readonly ?float $volume = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $description = null,
        public readonly string $source = 'unknown',
        public readonly array $raw = [],
    ) {}

    public function toArray(): array
    {
        return [
            'barcode' => $this->barcode,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->category,
            'unit_of_measure' => $this->unitOfMeasure,
            'weight' => $this->weight,
            'volume' => $this->volume,
            'image_url' => $this->imageUrl,
            'description' => $this->description,
            'source' => $this->source,
        ];
    }

    public function hasUsableData(): bool
    {
        return filled($this->name) || filled($this->brand) || filled($this->imageUrl);
    }
}
