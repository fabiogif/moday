<?php

namespace App\Repositories\Contracts;

use App\Models\Integrations\Ifood\IfoodOauthSession;

interface IfoodOauthSessionRepositoryInterface
{
    public function create(array $data): IfoodOauthSession;

    public function findByUserCode(string $userCode, int $tenantId): ?IfoodOauthSession;
}

