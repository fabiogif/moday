<?php

namespace App\Http\Responses\Dashboard;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

readonly class SalesPerformanceResponse implements Responsable
{
    public function __construct(
        private array $performance
    ) {}

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->performance,
            'message' => 'Desempenho de vendas carregado com sucesso',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    public function toArray(): array
    {
        return $this->performance;
    }
}
