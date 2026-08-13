<?php

namespace Tests\Feature\Visit;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VisitMediaUploadTest extends TestCase
{
    use GrantsDistribtecPermissions;

    private User $user;
    private Tenant $tenant;
    private Visit $visit;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->visit = Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->user->id, 'client_id' => $client->id]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token, 'Accept' => 'application/json'];
    }

    #[Test]
    public function it_uploads_a_photo_and_attaches_to_the_visit(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('visita.jpg', 800, 600);

        $response = $this->withHeaders($this->auth())
            ->post("/api/visits/{$this->visit->uuid}/media", ['type' => 'photo', 'file' => $photo]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'photo')
            ->assertJsonPath('data.file_name', 'visita.jpg');

        $this->assertDatabaseHas('visit_media', [
            'visit_id' => $this->visit->id,
            'type' => 'photo',
            'uploaded_by_user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function it_uploads_a_document(): void
    {
        Storage::fake('public');

        $document = UploadedFile::fake()->create('proposta.pdf', 500);

        $response = $this->withHeaders($this->auth())
            ->post("/api/visits/{$this->visit->uuid}/media", ['type' => 'document', 'file' => $document]);

        $response->assertStatus(201)->assertJsonPath('data.type', 'document');

        $this->assertDatabaseHas('visit_media', ['visit_id' => $this->visit->id, 'type' => 'document']);
    }

    #[Test]
    public function it_lists_media_of_a_visit(): void
    {
        Storage::fake('public');

        $this->withHeaders($this->auth())
            ->post("/api/visits/{$this->visit->uuid}/media", ['type' => 'photo', 'file' => UploadedFile::fake()->image('a.jpg')])
            ->assertStatus(201);

        $response = $this->withHeaders($this->auth())->getJson("/api/visits/{$this->visit->uuid}/media");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_validates_media_type(): void
    {
        Storage::fake('public');

        $this->withHeaders($this->auth())
            ->post("/api/visits/{$this->visit->uuid}/media", ['type' => 'invalid', 'file' => UploadedFile::fake()->image('a.jpg')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function it_returns_404_for_media_of_unknown_visit(): void
    {
        Storage::fake('public');

        $this->withHeaders($this->auth())
            ->post('/api/visits/' . \Illuminate\Support\Str::uuid() . '/media', ['type' => 'photo', 'file' => UploadedFile::fake()->image('a.jpg')])
            ->assertStatus(404);
    }
}
