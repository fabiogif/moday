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
            'name' => $this->capitalizeFirst($this->name),
            'brand' => $this->brand,
            'category' => $this->category,
            'unit_of_measure' => $this->unitOfMeasure,
            'weight' => $this->weight,
            'volume' => $this->volume,
            'image_url' => $this->imageUrl,
            'description' => $this->capitalizeFirst($this->description ?? $this->name),
            'source' => $this->source,
        ];
    }

    public function hasUsableData(): bool
    {
        return filled($this->name) || filled($this->brand) || filled($this->imageUrl);
    }

    private function capitalizeFirst(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        $first = mb_substr($trimmed, 0, 1, 'UTF-8');
        $rest = mb_substr($trimmed, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8') . $rest;
    }
}
