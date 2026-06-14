<?php

namespace App\Repositories\Contracts;

use App\Models\Integrations\Ifood\IfoodApiToken;
use Illuminate\Support\Collection;

interface IfoodTokenRepositoryInterface
{
    public function getActiveToken(int $tenantId): ?IfoodApiToken;

    public function storeToken(array $data): IfoodApiToken;

    public function deactivateTokens(int $tenantId, ?int $exceptId = null): void;

    /**
     * @return Collection<int, IfoodApiToken>
     */
    public function getTokensExpiringWithin(int $minutes): Collection;
}

