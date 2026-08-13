<?php

namespace Database\Factories;

use App\Models\PDVFeedback;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PDVFeedback>
 */
class PDVFeedbackFactory extends Factory
{
    protected $model = PDVFeedback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['suggestion', 'bug', 'praise', 'other']),
            'rating' => fake()->optional()->numberBetween(1, 5),
            'message' => fake()->optional()->sentence(),
            'quick_emoji' => fake()->optional()->randomElement(['😊', '😐', '😞']),
            'metadata' => [
                'browser' => fake()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
            'status' => 'pending',
        ];
    }

    /**
     * Indicate that the feedback is a suggestion.
     */
    public function suggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'suggestion',
        ]);
    }

    /**
     * Indicate that the feedback is a bug report.
     */
    public function bug(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bug',
        ]);
    }

    /**
     * Indicate that the feedback is praise.
     */
    public function praise(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'praise',
            'rating' => 5,
            'quick_emoji' => '😊',
        ]);
    }

    /**
     * Indicate that the feedback is reviewed.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewed',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that the feedback is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'review_notes' => 'Problema resolvido',
        ]);
    }
}

