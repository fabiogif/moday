<?php

namespace App\Repositories\Contracts;

interface LoyaltyRewardRepositoryInterface
{
    public function getAllByProgram(int $programId);
    public function getAvailableByProgram(int $programId);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

