<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\Tenant;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;
    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->service = app(NotificationService::class);
    }

    #[Test]
    public function pode_criar_preferencias_padrao_para_usuario()
    {
        $this->service->createDefaultPreferences($this->user->id, $this->tenant->id);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function pode_enviar_notificacao()
    {
        $result = $this->service->send(
            $this->user->id,
            $this->tenant->id,
            'order_created',
            [
                'title' => 'Novo Pedido',
                'message' => 'Pedido #123 criado',
            ]
        );

        $this->assertTrue($result);

        // Verificar que notificação foi criada
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'type' => 'order_created',
        ]);
    }

    #[Test]
    public function pode_obter_contagem_de_nao_lidas()
    {
        // Criar 3 notificações
        for ($i = 0; $i < 3; $i++) {
            $this->service->send(
                $this->user->id,
                $this->tenant->id,
                'test_event',
                ['title' => "Test {$i}", 'message' => "Message {$i}"]
            );
        }

        $count = $this->service->getUnreadCount($this->user->id, $this->tenant->id);

        $this->assertGreaterThanOrEqual(3, $count);
    }
}

