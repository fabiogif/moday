<?php

namespace App\Repositories\Contracts;

use App\Models\AdminUser;

interface AdminUserRepositoryInterface
{
    public function findByEmail(string $email): ?AdminUser;
}
