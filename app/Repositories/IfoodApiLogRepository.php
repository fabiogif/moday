<?php

namespace App\Repositories;

use App\Models\Integrations\Ifood\IfoodApiLog;
use App\Repositories\Contracts\IfoodApiLogRepositoryInterface;

class IfoodApiLogRepository implements IfoodApiLogRepositoryInterface
{
    public function __construct(
        private readonly IfoodApiLog $model = new IfoodApiLog()
    ) {
    }

    public function create(array $data): IfoodApiLog
    {
        if (empty($data['tenant_id'])) {
            throw new \InvalidArgumentException('tenant_id é obrigatório ao criar logs do iFood.');
        }

        return $this->model->create($data);
    }
}

