<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Tenant, Order, Review};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->accessible()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function can_create_review()
    {
        $data = [
            'tenant_id' => $this->tenant->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'comment' => 'Excelente serviço!',
            'customer_name' => 'João Silva',
            'customer_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/public/reviews', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Avaliação enviada! Será publicada após aprovação pela administração.',
            ]);

        $this->assertDatabaseHas('reviews', [
            'order_id' => $this->order->id,
            'rating' => 5,
            'status' => 'pending',
            'comment' => 'Excelente serviço!',
        ]);
    }

    #[Test]
    public function cannot_create_duplicate_review_for_order()
    {
        Review::factory()->create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $this->order->id,
        ]);

        $data = [
            'tenant_id' => $this->tenant->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'comment' => 'Teste',
            'customer_name' => 'João',
            'customer_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/public/reviews', $data);

        $response->assertStatus(500) // ApiResponseClass::rollback retorna 500
            ->assertJson([
                'success' => false,
                'message' => 'Este pedido já possui uma avaliação.',
            ]);
    }

    #[Test]
    public function validates_required_fields()
    {
        $response = $this->postJson('/api/public/reviews', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tenant_id', 'order_id', 'rating']);
    }

    #[Test]
    public function validates_rating_range()
    {
        $data = [
            'tenant_id' => $this->tenant->id,
            'order_id' => $this->order->id,
            'rating' => 6, // Inválido
            'customer_name' => 'João',
            'customer_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/public/reviews', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    #[Test]
    public function admin_can_approve_review()
    {
        $review = Review::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/reviews/{$review->uuid}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avaliação aprovada com sucesso!',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'approved',
            'moderated_by' => $this->user->id,
        ]);
    }

    #[Test]
    public function admin_can_reject_review()
    {
        $review = Review::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/reviews/{$review->uuid}/reject", [
                'reason' => 'Conteúdo inadequado'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avaliação rejeitada.',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'rejected',
            'moderation_reason' => 'Conteúdo inadequado',
        ]);
    }

    #[Test]
    public function only_approved_reviews_are_public()
    {
        // Criar várias avaliações com diferentes status
        Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Review::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Review::factory()->rejected()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson("/api/store/{$this->tenant->slug}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Apenas a aprovada
    }

    #[Test]
    public function admin_can_list_all_reviews()
    {
        Review::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/reviews');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function admin_can_filter_pending_reviews()
    {
        Review::factory()->pending()->count(2)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/reviews?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function admin_can_delete_review()
    {
        $review = Review::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/reviews/{$review->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avaliação removida com sucesso.',
            ]);

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    }

    #[Test]
    public function can_toggle_featured_review()
    {
        $review = Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'is_featured' => false,
        ]);

        // Toggle para TRUE
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/reviews/{$review->uuid}/toggle-featured");

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_featured' => true,
        ]);

        // Toggle para FALSE
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/reviews/{$review->uuid}/toggle-featured");

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_featured' => false,
        ]);
    }

    #[Test]
    public function review_stats_are_correct()
    {
        Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 5
        ]);
        Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 4
        ]);
        Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 5
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/reviews/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 3,
                    'average_rating' => 4.67,
                ],
            ]);
    }

    #[Test]
    public function can_get_featured_reviews()
    {
        Review::factory()->featured()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'is_featured' => false,
        ]);

        $response = $this->getJson("/api/store/{$this->tenant->slug}/reviews/featured");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function can_increment_helpful_count()
    {
        $review = Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'helpful_count' => 0,
        ]);

        $response = $this->postJson("/api/public/reviews/{$review->uuid}/helpful");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Obrigado pelo feedback!',
                'data' => [
                    'helpful_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'helpful_count' => 1,
        ]);
    }

    #[Test]
    public function can_get_recent_reviews_for_dashboard()
    {
        Review::factory()->approved()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/reviews/recent?limit=3');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function unauthenticated_user_cannot_access_admin_routes()
    {
        $review = Review::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/reviews');
        $response->assertStatus(401);

        $response = $this->postJson("/api/reviews/{$review->uuid}/approve");
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/reviews/{$review->uuid}");
        $response->assertStatus(401);
    }

    #[Test]
    public function comment_is_optional()
    {
        $data = [
            'tenant_id' => $this->tenant->id,
            'order_id' => $this->order->id,
            'rating' => 4,
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            // Sem comentário
        ];

        $response = $this->postJson('/api/public/reviews', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'order_id' => $this->order->id,
            'rating' => 4,
            'comment' => null,
        ]);
    }

    #[Test]
    public function email_is_optional_for_public_reviews()
    {
        $data = [
            'tenant_id' => $this->tenant->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'customer_name' => 'Carlos Pereira',
            // Sem e-mail
        ];

        $response = $this->postJson('/api/public/reviews', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('reviews', [
            'order_id' => $this->order->id,
            'customer_name' => 'Carlos Pereira',
            'customer_email' => null,
        ]);
    }

    #[Test]
    public function validates_comment_max_length()
    {
        $data = [
            'tenant_id' => $this->tenant->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'comment' => str_repeat('a', 1001), // Excede 1000
            'customer_name' => 'João',
            'customer_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/public/reviews', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    #[Test]
    public function public_stats_endpoint_works()
    {
        Review::factory()->approved()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson("/api/store/{$this->tenant->slug}/reviews/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'average_rating',
                    'rating_distribution',
                    'rating_percentages',
                ],
            ]);
    }
}

