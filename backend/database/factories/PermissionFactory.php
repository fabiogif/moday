<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['create', 'read', 'update', 'delete'];
        $resources = ['users', 'products', 'orders', 'categories', 'tenants'];
        
        $action = fake()->randomElement($actions);
        $resource = fake()->randomElement($resources);
        $name = "{$action}_{$resource}";

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => "Permissão para {$action} {$resource}",
        ];
    }

    /**
     * Indicate that the permission is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Define ação e recurso explicitamente, evitando conflito com Factory::for().
     */
    public function withActionResource(string $action, string $resource): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "{$action}_{$resource}",
            'description' => "Permissão para {$action} {$resource}",
            'group' => $resource,
        ]);
    }
}
