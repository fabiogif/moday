<?php

namespace App\Repositories;

use App\Models\Integrations\Ifood\IfoodApiToken;
use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use Illuminate\Support\Collection;

class IfoodTokenRepository implements IfoodTokenRepositoryInterface
{
    public function __construct(
        private readonly IfoodApiToken $model = new IfoodApiToken()
    ) {
    }

    public function getActiveToken(int $tenantId): ?IfoodApiToken
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->first();
    }

    public function storeToken(array $data): IfoodApiToken
    {
        $token = $this->model->create($data);

        $this->deactivateTokens($token->tenant_id, $token->id);

        return $token->fresh();
    }

    public function deactivateTokens(int $tenantId, ?int $exceptId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_active' => false]);
    }

    public function getTokensExpiringWithin(int $minutes): Collection
    {
        $threshold = now()->addMinutes($minutes);

        return $this->model->newQuery()
            ->where('is_active', true)
            ->where('expires_at', '<=', $threshold)
            ->get();
    }
}

