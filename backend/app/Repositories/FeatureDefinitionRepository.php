<?php

namespace App\Repositories;

use App\Models\FeatureDefinition;
use App\Repositories\Contracts\FeatureDefinitionRepositoryInterface;
use Illuminate\Support\Collection;

class FeatureDefinitionRepository implements FeatureDefinitionRepositoryInterface
{
    public function allOrdered(): Collection
    {
        return FeatureDefinition::orderBy('category')
            ->orderBy('display_order')
            ->get();
    }

    public function findByKey(string $key): ?FeatureDefinition
    {
        return FeatureDefinition::where('key', $key)->first();
    }

    public function create(array $data): FeatureDefinition
    {
        return FeatureDefinition::create($data);
    }

    public function update(int $id, array $data): FeatureDefinition
    {
        $definition = FeatureDefinition::findOrFail($id);
        $definition->update($data);
        return $definition->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) FeatureDefinition::findOrFail($id)->delete();
    }
}
