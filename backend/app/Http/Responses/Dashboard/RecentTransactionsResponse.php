<?php

namespace App\Http\Responses\Dashboard;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

readonly class RecentTransactionsResponse implements Responsable
{
    public function __construct(
        private array $transactions
    ) {}

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->transactions,
            'message' => 'Transações recentes carregadas com sucesso',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    public function toArray(): array
    {
        return $this->transactions;
    }
}
