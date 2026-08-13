<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Tenant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            'Comida excelente! Muito saborosa e bem temperada.',
            'Atendimento impecável, entrega rápida.',
            'Produto de qualidade, recomendo!',
            'Muito bom, mas poderia melhorar a embalagem.',
            'Entrega atrasou um pouco, mas a comida estava ótima.',
            'Perfeito! Vou pedir novamente com certeza.',
            'Boa comida, preço justo.',
            'Adorei! Superou minhas expectativas.',
        ];

        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'user_id' => $this->faker->optional(0.7)->randomElement([User::factory()]),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional(0.8)->randomElement($comments),
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->safeEmail,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'is_featured' => false,
            'helpful_count' => $this->faker->numberBetween(0, 50),
            'moderated_by' => null,
            'moderated_at' => null,
            'moderation_reason' => null,
        ];
    }

    /**
     * Estado: Aprovada
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'moderated_by' => User::factory(),
            'moderated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'moderation_reason' => null,
        ]);
    }

    /**
     * Estado: Pendente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'moderated_by' => null,
            'moderated_at' => null,
            'moderation_reason' => null,
        ]);
    }

    /**
     * Estado: Rejeitada
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'moderated_by' => User::factory(),
            'moderated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'moderation_reason' => $this->faker->randomElement([
                'Conteúdo inadequado',
                'Linguagem ofensiva',
                'Spam',
                'Informações falsas',
            ]),
        ]);
    }

    /**
     * Estado: Em destaque
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'approved',
            'rating' => $this->faker->numberBetween(4, 5), // Destaques geralmente são 4-5 estrelas
            'moderated_by' => User::factory(),
            'moderated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Estado: Com nota alta (4-5 estrelas)
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(4, 5),
        ]);
    }

    /**
     * Estado: Com nota baixa (1-2 estrelas)
     */
    public function lowRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Estado: Sem comentário
     */
    public function withoutComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => null,
        ]);
    }

    /**
     * Estado: Com comentário longo
     */
    public function withLongComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => $this->faker->paragraph(5),
        ]);
    }
}

