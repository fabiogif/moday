<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Tenant, Order, Review};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tenant;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->accessible()->create();
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->token = auth('api')->login($this->admin);
        $this->defaultHeaders['Authorization'] = 'Bearer ' . $this->token;
    }

    #[Test]
    public function admin_can_view_all_pending_reviews()
    {
        Review::factory()->pending()->count(5)->create(['tenant_id' => $this->tenant->id]);
        Review::factory()->approved()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/reviews/pending');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function approved_review_can_be_featured()
    {
        $review = Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/reviews/{$review->uuid}/toggle-featured");

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_featured' => true,
        ]);
    }

    #[Test]
    public function pending_review_cannot_be_featured()
    {
        $review = Review::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/reviews/{$review->uuid}/toggle-featured");

        // ApiResponseClass::rollback retorna 500
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Apenas avaliações aprovadas podem ser destacadas.',
            ]);
    }

    #[Test]
    public function rejected_review_cannot_be_featured()
    {
        $review = Review::factory()->rejected()->create([
            'tenant_id' => $this->tenant->id,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/reviews/{$review->uuid}/toggle-featured");

        // ApiResponseClass::rollback retorna 500
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Apenas avaliações aprovadas podem ser destacadas.',
            ]);
    }

    #[Test]
    public function moderation_records_moderator_info()
    {
        $review = Review::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/reviews/{$review->uuid}/approve");

        $review->refresh();

        $this->assertEquals($this->admin->id, $review->moderated_by);
        $this->assertNotNull($review->moderated_at);
        $this->assertEquals('approved', $review->status);
    }

    #[Test]
    public function reject_reason_is_saved()
    {
        $review = Review::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/reviews/{$review->uuid}/reject", [
                'reason' => 'Linguagem ofensiva'
            ]);

        $review->refresh();

        $this->assertEquals('Linguagem ofensiva', $review->moderation_reason);
        $this->assertEquals('rejected', $review->status);
    }

    #[Test]
    public function can_filter_reviews_by_multiple_criteria()
    {
        // Criar avaliações variadas
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 5, 'status' => 'approved']);
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 4, 'status' => 'approved']);
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 5, 'status' => 'pending']);
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 3, 'status' => 'rejected']);

        // Filtrar por status
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/reviews?status=approved');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function moderation_updates_only_target_review()
    {
        $review1 = Review::factory()->pending()->create(['tenant_id' => $this->tenant->id]);
        $review2 = Review::factory()->pending()->create(['tenant_id' => $this->tenant->id]);

        // Aprovar apenas review1
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/reviews/{$review1->uuid}/approve");

        $review1->refresh();
        $review2->refresh();

        $this->assertEquals('approved', $review1->status);
        $this->assertEquals('pending', $review2->status);
    }

    #[Test]
    public function admin_can_view_moderation_history()
    {
        $review = Review::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'moderated_by' => $this->admin->id,
            'moderated_at' => now(),
            'moderation_reason' => 'Conteúdo apropriado',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/reviews/{$review->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'moderator' => ['id', 'name', 'moderated_at'],
                    'moderation_reason'
                ],
            ]);
    }

    #[Test]
    public function deleting_review_soft_deletes()
    {
        $review = Review::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/reviews/{$review->uuid}");

        // Verifica soft delete
        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
        
        // Mas ainda existe no banco
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    #[Test]
    public function can_get_reviews_with_different_ratings()
    {
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 5, 'status' => 'approved']);
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 4, 'status' => 'approved']);
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 3, 'status' => 'approved']);
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 2, 'status' => 'approved']);
        Review::factory()->create(['tenant_id' => $this->tenant->id, 'rating' => 1, 'status' => 'approved']);

        $response = $this->getJson("/api/store/{$this->tenant->slug}/reviews/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'average_rating',
                    'rating_distribution' => ['5', '4', '3', '2', '1'],
                    'rating_percentages' => ['5', '4', '3', '2', '1'],
                ],
            ]);
    }
}

