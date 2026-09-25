<?php

namespace App\Repositories\Contracts;

use App\Models\Integrations\Ifood\IfoodApiLog;

interface IfoodApiLogRepositoryInterface
{
    public function create(array $data): IfoodApiLog;
}

