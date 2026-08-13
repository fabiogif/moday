<?php

namespace App\Repositories;

use App\Models\AdminUser;
use App\Repositories\Contracts\AdminUserRepositoryInterface;

class AdminUserRepository implements AdminUserRepositoryInterface
{
    public function findByEmail(string $email): ?AdminUser
    {
        return AdminUser::where('email', $email)->first();
    }
}
