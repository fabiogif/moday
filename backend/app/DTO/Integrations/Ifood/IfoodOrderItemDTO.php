<?php

namespace App\DTO\Integrations\Ifood;

use Illuminate\Support\Arr;

class IfoodOrderItemDTO
{
    public function __construct(
        public readonly ?int $sequence,
        public readonly ?string $externalItemId,
        public readonly ?string $uniqueId,
        public readonly ?string $externalCode,
        public readonly ?string $sku,
        public readonly ?string $ean,
        public readonly string $name,
        public readonly float $quantity,
        public readonly ?float $quantityValue,
        public readonly ?string $unit,
        public readonly ?float $unitPrice,
        public readonly ?float $price,
        public readonly ?float $totalPrice,
        public readonly ?float $optionsPrice,
        public readonly ?float $customizationPrice,
        public readonly ?string $observations,
        public readonly array $options,
        public readonly array $scalePrices,
        public readonly array $metadata = [],
    ) {
    }

    public static function fromArray(array $payload): self
    {
        $priceNode = $payload['price'] ?? [];

        return new self(
            sequence: isset($payload['index']) ? (int) $payload['index'] : null,
            externalItemId: $payload['id'] ?? $payload['external_item_id'] ?? null,
            uniqueId: $payload['uniqueId'] ?? $payload['unique_id'] ?? null,
            externalCode: $payload['externalCode'] ?? null,
            sku: $payload['sku'] ?? null,
            ean: $payload['ean'] ?? null,
            name: $payload['name'] ?? '',
            quantity: (float) ($payload['quantity'] ?? 0),
            quantityValue: isset($payload['quantity']) ? (float) $payload['quantity'] : null,
            unit: $payload['unit'] ?? null,
            unitPrice: self::normalizeMoney(Arr::get($priceNode, 'unit', $payload['unit_price'] ?? null)),
            price: self::normalizeMoney(Arr::get($priceNode, 'price', $payload['price_value'] ?? null)),
            totalPrice: self::normalizeMoney(Arr::get($priceNode, 'total', $payload['total_price'] ?? null)),
            optionsPrice: self::normalizeMoney($payload['optionsPrice'] ?? null),
            customizationPrice: self::normalizeMoney($payload['customizationPrice'] ?? null),
            observations: $payload['observations'] ?? null,
            options: $payload['options'] ?? [],
            scalePrices: $payload['scalePrices'] ?? [],
            metadata: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'sequence' => $this->sequence,
            'external_item_id' => $this->externalItemId,
            'unique_id' => $this->uniqueId,
            'external_code' => $this->externalCode,
            'sku' => $this->sku,
            'ean' => $this->ean,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'quantity_value' => $this->quantityValue,
            'unit' => $this->unit,
            'unit_price' => $this->unitPrice,
            'price' => $this->price,
            'total_price' => $this->totalPrice,
            'options_price' => $this->optionsPrice,
            'customization_price' => $this->customizationPrice,
            'observations' => $this->observations,
            'options' => $this->options,
            'scale_prices' => $this->scalePrices,
            'metadata' => $this->metadata,
        ];
    }

    private static function normalizeMoney(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $amount = Arr::get($value, 'amount', Arr::get($value, 'value'));
            return self::normalizeMoney($amount);
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value / 100;
    }
}


