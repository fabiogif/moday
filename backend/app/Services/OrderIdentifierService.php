<?php

namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Str;

class OrderIdentifierService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function generate(): string
    {
        do {
            $identify = strtoupper(Str::random(8));
        } while ($this->orderRepository->identifyExists($identify));

        return $identify;
    }
}
