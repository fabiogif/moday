<?php

namespace App\DTO\Integrations\Ifood;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class IfoodOrderDTO
{
    /**
     * @param array<IfoodOrderItemDTO> $items
     */
    public function __construct(
        public readonly string $externalOrderId,
        public readonly string $displayId,
        public readonly ?CarbonInterface $createdAt,
        public readonly ?CarbonInterface $preparationStart,
        public readonly ?string $orderType,
        public readonly ?string $orderTiming,
        public readonly ?string $salesChannel,
        public readonly ?string $category,
        public readonly bool $isTest,
        public readonly ?string $extraInfo,
        public readonly array $merchant,
        public readonly array $customer,
        public readonly array $items,
        public readonly array $delivery,
        public readonly array $takeout,
        public readonly array $dinein,
        public readonly array $schedule,
        public readonly array $additionalInfo,
        public readonly float $totalAmount,
        public readonly ?float $itemsTotal,
        public readonly ?float $deliveryFee,
        public readonly ?float $benefitsTotal,
        public readonly ?float $additionalFeesTotal,
        public readonly array $benefits,
        public readonly array $additionalFees,
        public readonly array $payments,
        public readonly array $picking,
        public readonly array $rawPayload,
        public readonly array $statusHistory,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        $items = array_map(
            static fn (array $item) => IfoodOrderItemDTO::fromArray($item),
            $payload['items'] ?? []
        );

        $totalNode = $payload['total'] ?? [];
        $totalAmount = Arr::get($totalNode, 'orderAmount', Arr::get($totalNode, 'total', 0));
        $itemsTotal = Arr::get($totalNode, 'subTotal', Arr::get($totalNode, 'itemsPrice', Arr::get($totalNode, 'subTotalValue', null)));
        $deliveryFee = Arr::get($totalNode, 'deliveryFee');

        $benefits = $payload['benefits'] ?? [];
        $additionalFees = $payload['additionalFees'] ?? [];

        return new self(
            externalOrderId: $payload['id'],
            displayId: $payload['displayId'] ?? $payload['display_id'] ?? $payload['id'],
            createdAt: isset($payload['createdAt']) ? now()->parse($payload['createdAt']) : null,
            preparationStart: isset($payload['preparationStartDateTime'])
                ? now()->parse($payload['preparationStartDateTime'])
                : (isset($payload['preparationStartTime']) ? now()->parse($payload['preparationStartTime']) : null),
            orderType: $payload['orderType'] ?? null,
            orderTiming: $payload['orderTiming'] ?? null,
            salesChannel: $payload['salesChannel'] ?? null,
            category: $payload['category'] ?? null,
            isTest: (bool) ($payload['isTest'] ?? false),
            extraInfo: $payload['extraInfo'] ?? null,
            merchant: $payload['merchant'] ?? [],
            customer: $payload['customer'] ?? [],
            items: $items,
            delivery: $payload['delivery'] ?? [],
            takeout: $payload['takeout'] ?? [],
            dinein: $payload['dineIn'] ?? ($payload['dinein'] ?? []),
            schedule: $payload['schedule'] ?? [],
            additionalInfo: $payload['additionalInfo'] ?? [],
            totalAmount: self::normalizeMoney($totalAmount),
            itemsTotal: self::normalizeMoney($itemsTotal),
            deliveryFee: self::normalizeMoney($deliveryFee),
            benefitsTotal: self::sumMonetaryFromArray($benefits, 'value'),
            additionalFeesTotal: self::sumMonetaryFromArray($additionalFees, 'price'),
            benefits: $benefits,
            additionalFees: $additionalFees,
            payments: $payload['payments'] ?? [],
            picking: $payload['picking'] ?? [],
            rawPayload: $payload,
            statusHistory: $payload['status'] ?? [],
        );
    }

    public function getCustomerName(): ?string
    {
        return $this->customer['name'] ?? null;
    }

    public function getCustomerPhone(): ?string
    {
        $phone = $this->customer['phone'] ?? null;

        if (is_array($phone)) {
            return $phone['number'] ?? null;
        }

        return $phone;
    }

    public function getDeliveryAddress(): array
    {
        return Arr::get($this->delivery, 'deliveryAddress', []);
    }

    public function getCustomerDocumentNumber(): ?string
    {
        return Arr::get($this->customer, 'documentNumber')
            ?? Arr::get($this->customer, 'document.number');
    }

    public function getCustomerOrdersCount(): ?int
    {
        return Arr::get($this->customer, 'ordersCountOnMerchant');
    }

    public function getCustomerSegmentation(): ?string
    {
        return Arr::get($this->customer, 'segmentation');
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

        // API retorna valores em centavos
        return (float) $value / 100;
    }

    private static function sumMonetaryFromArray(array $entries, string $key): ?float
    {
        if (empty($entries)) {
            return null;
        }

        $sum = 0.0;

        foreach ($entries as $entry) {
            $value = Arr::get($entry, $key);
            if ($value === null && isset($entry['total']['amount'])) {
                $value = $entry['total']['amount'];
            }

            $normalized = self::normalizeMoney($value);

            if ($normalized !== null) {
                $sum += $normalized;
            }
        }

        return $sum > 0 ? $sum : null;
    }
}

