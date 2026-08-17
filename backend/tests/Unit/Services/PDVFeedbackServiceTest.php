<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\PDVFeedback;
use App\Services\PDVFeedbackService;
use App\Repositories\PDVFeedbackRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PDVFeedbackServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PDVFeedbackService $service;
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $repository = new PDVFeedbackRepository();
        $this->service = new PDVFeedbackService($repository);
    }

    #[Test]
    public function pode_criar_feedback_com_dados_validos()
    {
        $data = [
            'type' => 'suggestion',
            'rating' => 5,
            'message' => 'Sugestão de melhoria',
            'quick_emoji' => '😊',
            'metadata' => ['browser' => 'Chrome'],
        ];

        $feedback = $this->service->createFeedback($data, $this->tenant->id, $this->user->id);

        $this->assertInstanceOf(PDVFeedback::class, $feedback);
        $this->assertEquals($this->tenant->id, $feedback->tenant_id);
        $this->assertEquals($this->user->id, $feedback->user_id);
        $this->assertEquals('suggestion', $feedback->type);
        $this->assertEquals(5, $feedback->rating);
        $this->assertEquals('Sugestão de melhoria', $feedback->message);
        $this->assertEquals('pending', $feedback->status);

        $this->assertDatabaseHas('pdv_feedbacks', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'type' => 'suggestion',
            'rating' => 5,
        ]);
    }

    #[Test]
    public function pode_criar_feedback_sem_usuario()
    {
        $data = [
            'type' => 'bug',
            'message' => 'Encontrei um bug',
        ];

        $feedback = $this->service->createFeedback($data, $this->tenant->id, null);

        $this->assertInstanceOf(PDVFeedback::class, $feedback);
        $this->assertNull($feedback->user_id);
    }

    #[Test]
    public function pode_buscar_feedback_por_uuid()
    {
        $feedback = PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'bug',
        ]);

        $found = $this->service->getFeedbackByUuid($feedback->uuid, $this->tenant->id);

        $this->assertInstanceOf(PDVFeedback::class, $found);
        $this->assertEquals($feedback->uuid, $found->uuid);
        $this->assertEquals('bug', $found->type);
    }

    #[Test]
    public function retorna_null_quando_feedback_nao_existe()
    {
        $found = $this->service->getFeedbackByUuid('non-existent-uuid', $this->tenant->id);

        $this->assertNull($found);
    }

    #[Test]
    public function nao_retorna_feedback_de_outro_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $feedback = PDVFeedback::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $found = $this->service->getFeedbackByUuid($feedback->uuid, $this->tenant->id);

        $this->assertNull($found);
    }

    #[Test]
    public function pode_listar_feedbacks_por_tenant()
    {
        PDVFeedback::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $otherTenant = Tenant::factory()->create();
        PDVFeedback::factory()->count(3)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $result = $this->service->getFeedbacksByTenant($this->tenant->id);

        $this->assertCount(5, $result->items());
    }

    #[Test]
    public function pode_filtrar_feedbacks_por_tipo()
    {
        PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'bug',
        ]);

        PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'suggestion',
        ]);

        $result = $this->service->getFeedbacksByTenant($this->tenant->id, 'bug');

        $this->assertCount(1, $result->items());
        $this->assertEquals('bug', $result->items()[0]->type);
    }

    #[Test]
    public function pode_filtrar_feedbacks_por_status()
    {
        PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'resolved',
        ]);

        $result = $this->service->getFeedbacksByTenant($this->tenant->id, null, 'pending');

        $this->assertCount(1, $result->items());
        $this->assertEquals('pending', $result->items()[0]->status);
    }

    #[Test]
    public function pode_atualizar_status_do_feedback()
    {
        $feedback = PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $updated = $this->service->updateFeedbackStatus(
            $feedback->uuid,
            $this->tenant->id,
            'reviewed',
            $this->user->id,
            'Feedback analisado'
        );

        $this->assertTrue($updated);

        $feedback->refresh();
        $this->assertEquals('reviewed', $feedback->status);
        $this->assertEquals($this->user->id, $feedback->reviewed_by);
        $this->assertEquals('Feedback analisado', $feedback->review_notes);
        $this->assertNotNull($feedback->reviewed_at);
    }

    #[Test]
    public function nao_atualiza_status_com_status_invalido()
    {
        $feedback = PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status inválido: invalid_status');

        $this->service->updateFeedbackStatus(
            $feedback->uuid,
            $this->tenant->id,
            'invalid_status',
            $this->user->id
        );
    }

    #[Test]
    public function retorna_false_ao_tentar_atualizar_feedback_inexistente()
    {
        $updated = $this->service->updateFeedbackStatus(
            'non-existent-uuid',
            $this->tenant->id,
            'reviewed',
            $this->user->id
        );

        $this->assertFalse($updated);
    }

    #[Test]
    public function nao_atualiza_feedback_de_outro_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $feedback = PDVFeedback::factory()->create([
            'tenant_id' => $otherTenant->id,
            'status' => 'pending',
        ]);

        $updated = $this->service->updateFeedbackStatus(
            $feedback->uuid,
            $this->tenant->id,
            'reviewed',
            $this->user->id
        );

        $this->assertFalse($updated);

        $feedback->refresh();
        $this->assertEquals('pending', $feedback->status);
    }

    #[Test]
    public function pode_atualizar_status_sem_notas_de_revisao()
    {
        $feedback = PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $updated = $this->service->updateFeedbackStatus(
            $feedback->uuid,
            $this->tenant->id,
            'resolved',
            $this->user->id,
            null
        );

        $this->assertTrue($updated);

        $feedback->refresh();
        $this->assertEquals('resolved', $feedback->status);
        $this->assertNull($feedback->review_notes);
    }

    #[Test]
    public function pode_atualizar_status_sem_reviewer()
    {
        $feedback = PDVFeedback::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $updated = $this->service->updateFeedbackStatus(
            $feedback->uuid,
            $this->tenant->id,
            'reviewed',
            null,
            null
        );

        $this->assertTrue($updated);

        $feedback->refresh();
        $this->assertEquals('reviewed', $feedback->status);
        $this->assertNull($feedback->reviewed_by);
    }
}

