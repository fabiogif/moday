<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeliveryPickupSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Restaurante Teste',
            'slug' => 'restaurante-teste',
        ]);

        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        // Gerar token JWT
        $this->token = JWTAuth::fromUser($this->user);
    }

    /**
     * Teste: Salvar configurações de delivery e retirada no tenant
     */
    public function test_can_save_delivery_pickup_settings()
    {
        $settings = [
            'delivery_pickup' => [
                'pickup_enabled' => true,
                'pickup_time_minutes' => 40,
                'pickup_discount_enabled' => true,
                'pickup_discount_percent' => 10,
                'delivery_enabled' => true,
                'delivery_minimum_order_enabled' => true,
                'delivery_minimum_order_value' => 25.00,
                'delivery_free_above_enabled' => true,
                'delivery_free_above_value' => 350.00,
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => $settings
        ]);

        $response->assertStatus(200);

        // Verificar que foi salvo no banco
        $this->tenant->refresh();
        $this->assertNotNull($this->tenant->settings);
        $this->assertEquals(40, $this->tenant->settings['delivery_pickup']['pickup_time_minutes']);
        $this->assertEquals(10, $this->tenant->settings['delivery_pickup']['pickup_discount_percent']);
        $this->assertEquals(25.00, $this->tenant->settings['delivery_pickup']['delivery_minimum_order_value']);
    }

    /**
     * Teste: Configurações padrão quando não existem
     */
    public function test_tenant_without_settings_returns_empty_array()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);
        
        $settings = $response->json('data.tenant.settings');
        $this->assertTrue(is_array($settings) || is_null($settings));
    }

    /**
     * Teste: Apenas retirada habilitada
     */
    public function test_can_enable_only_pickup()
    {
        $settings = [
            'delivery_pickup' => [
                'pickup_enabled' => true,
                'pickup_time_minutes' => 30,
                'delivery_enabled' => false,
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => $settings
        ]);

        $response->assertStatus(200);

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->settings['delivery_pickup']['pickup_enabled']);
        $this->assertFalse($this->tenant->settings['delivery_pickup']['delivery_enabled']);
    }

    /**
     * Teste: Apenas delivery habilitado
     */
    public function test_can_enable_only_delivery()
    {
        $settings = [
            'delivery_pickup' => [
                'pickup_enabled' => false,
                'delivery_enabled' => true,
                'delivery_minimum_order_value' => 30.00,
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => $settings
        ]);

        $response->assertStatus(200);

        $this->tenant->refresh();
        $this->assertFalse($this->tenant->settings['delivery_pickup']['pickup_enabled']);
        $this->assertTrue($this->tenant->settings['delivery_pickup']['delivery_enabled']);
    }

    /**
     * Teste: Desconto de retirada aplicado corretamente
     */
    public function test_pickup_discount_can_be_configured()
    {
        $settings = [
            'delivery_pickup' => [
                'pickup_enabled' => true,
                'pickup_discount_enabled' => true,
                'pickup_discount_percent' => 15.5,
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => $settings
        ]);

        $response->assertStatus(200);

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->settings['delivery_pickup']['pickup_discount_enabled']);
        $this->assertEquals(15.5, $this->tenant->settings['delivery_pickup']['pickup_discount_percent']);
    }

    /**
     * Teste: Pedido mínimo de delivery
     */
    public function test_delivery_minimum_order_can_be_configured()
    {
        $settings = [
            'delivery_pickup' => [
                'delivery_enabled' => true,
                'delivery_minimum_order_enabled' => true,
                'delivery_minimum_order_value' => 50.00,
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => $settings
        ]);

        $response->assertStatus(200);

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->settings['delivery_pickup']['delivery_minimum_order_enabled']);
        $this->assertEquals(50.00, $this->tenant->settings['delivery_pickup']['delivery_minimum_order_value']);
    }

    /**
     * Teste: Entrega grátis acima de valor
     */
    public function test_delivery_free_above_can_be_configured()
    {
        $settings = [
            'delivery_pickup' => [
                'delivery_enabled' => true,
                'delivery_free_above_enabled' => true,
                'delivery_free_above_value' => 500.00,
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => $settings
        ]);

        $response->assertStatus(200);

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->settings['delivery_pickup']['delivery_free_above_enabled']);
        $this->assertEquals(500.00, $this->tenant->settings['delivery_pickup']['delivery_free_above_value']);
    }

    /**
     * Teste: Todas as configurações juntas
     */
    public function test_can_save_all_settings_together()
    {
        $settings = [
            'delivery_pickup' => [
                // Retirada
                'pickup_enabled' => true,
                'pickup_time_minutes' => 45,
                'pickup_discount_enabled' => true,
                'pickup_discount_percent' => 12.5,
                
                // Delivery
                'delivery_enabled' => true,
                'delivery_minimum_order_enabled' => true,
                'delivery_minimum_order_value' => 35.00,
                'delivery_free_above_enabled' => true,
                'delivery_free_above_value' => 400.00,
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => $settings
        ]);

        $response->assertStatus(200);

        $this->tenant->refresh();
        $config = $this->tenant->settings['delivery_pickup'];
        
        $this->assertTrue($config['pickup_enabled']);
        $this->assertEquals(45, $config['pickup_time_minutes']);
        $this->assertEquals(12.5, $config['pickup_discount_percent']);
        $this->assertEquals(35.00, $config['delivery_minimum_order_value']);
        $this->assertEquals(400.00, $config['delivery_free_above_value']);
    }

    /**
     * Teste: Atualizar apenas algumas configurações
     */
    public function test_can_update_partial_settings()
    {
        // Primeiro, salvar configurações iniciais
        $this->tenant->update([
            'settings' => [
                'delivery_pickup' => [
                    'pickup_enabled' => true,
                    'pickup_time_minutes' => 35,
                    'delivery_enabled' => true,
                ]
            ]
        ]);

        // Atualizar apenas tempo de retirada
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/tenant/{$this->tenant->uuid}", [
            'settings' => [
                'delivery_pickup' => [
                    'pickup_time_minutes' => 50,
                ]
            ]
        ]);

        $response->assertStatus(200);
    }
}

