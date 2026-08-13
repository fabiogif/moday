<?php

namespace App\Http\Responses\Dashboard;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

readonly class TopProductsResponse implements Responsable
{
    public function __construct(
        private array $products
    ) {}

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->products,
            'message' => 'Principais produtos carregados com sucesso',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    public function toArray(): array
    {
        return $this->products;
    }
}
