<?php

namespace App\Http\Responses\Dashboard;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

readonly class DashboardMetricsResponse implements Responsable
{
    public function __construct(
        private array $metrics
    ) {}

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->metrics,
            'message' => 'Métricas carregadas com sucesso',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    public function toArray(): array
    {
        return $this->metrics;
    }
}
