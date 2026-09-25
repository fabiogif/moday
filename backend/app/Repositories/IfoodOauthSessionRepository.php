<?php

namespace App\Repositories;

use App\Models\Integrations\Ifood\IfoodOauthSession;
use App\Repositories\Contracts\IfoodOauthSessionRepositoryInterface;

class IfoodOauthSessionRepository implements IfoodOauthSessionRepositoryInterface
{
    public function __construct(
        private readonly IfoodOauthSession $model = new IfoodOauthSession()
    ) {
    }

    public function create(array $data): IfoodOauthSession
    {
        return $this->model->newQuery()->create($data);
    }

    public function findByUserCode(string $userCode, int $tenantId): ?IfoodOauthSession
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_code', $userCode)
            ->first();
    }
}

