<?php

namespace App\DTO\Integrations\Ifood;

class IfoodOrderStatusDTO
{
    public function __construct(
        public readonly string $externalOrderId,
        public readonly string $status,
        public readonly ?string $integrationId = null,
        public readonly ?string $reason = null,
        public readonly ?string $description = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'orderId' => $this->externalOrderId,
            'status' => $this->status,
            'integrationId' => $this->integrationId,
            'reason' => $this->reason,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}

