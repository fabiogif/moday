<?php

namespace App\Repositories\Contracts;

use App\Models\FeatureDefinition;
use Illuminate\Support\Collection;

interface FeatureDefinitionRepositoryInterface
{
    public function allOrdered(): Collection;

    public function findByKey(string $key): ?FeatureDefinition;

    public function create(array $data): FeatureDefinition;

    public function update(int $id, array $data): FeatureDefinition;

    public function delete(int $id): bool;
}
