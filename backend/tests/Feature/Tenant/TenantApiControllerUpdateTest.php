<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TenantApiControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
            'Accept' => 'application/json',
        ];
    }

    #[Test]
    public function usuario_nao_consegue_atualizar_tenant_de_outra_empresa(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $otherTenantOwner = User::factory()->create(['email_verified_at' => now()]);
        $otherTenant = $otherTenantOwner->tenant;

        $response = $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/tenant/{$otherTenant->uuid}", [
                'name' => 'Nome Invadido',
            ]);

        $response->assertStatus(404);
        $this->assertNotEquals('Nome Invadido', $otherTenant->fresh()->name);
    }

    #[Test]
    public function usuario_atualiza_o_proprio_tenant(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $response = $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/tenant/{$tenant->uuid}", [
                'name' => 'Novo Nome da Empresa',
            ]);

        $response->assertOk();
        $this->assertSame('Novo Nome da Empresa', $tenant->fresh()->name);
    }

    #[Test]
    public function atualizar_settings_faz_merge_em_vez_de_substituir(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;
        $tenant->update(['settings' => ['delivery_pickup' => ['pickup_enabled' => true]]]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/tenant/{$tenant->uuid}", [
                'settings' => ['require_email_verification' => false],
            ]);

        $response->assertOk();

        $fresh = $tenant->fresh()->settings;
        $this->assertFalse($fresh['require_email_verification']);
        $this->assertTrue($fresh['delivery_pickup']['pickup_enabled']);
    }

    #[Test]
    public function post_tenant_validate_nao_e_capturado_pela_rota_de_update(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $response = $this->withHeaders($this->authHeaders($owner))
            ->postJson('/api/tenant/validate', [
                'step' => 1,
                'uuid' => $tenant->uuid,
                'name' => 'Nome Válido',
                'email' => 'valido@example.com',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function tenant_validate_retorna_erros_de_validacao_do_campo(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $response = $this->withHeaders($this->authHeaders($owner))
            ->postJson('/api/tenant/validate', [
                'step' => 1,
                'uuid' => $tenant->uuid,
                'name' => '',
                'email' => 'nao-e-um-email',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email']);
    }

    #[Test]
    public function tenant_validate_nao_permite_validar_uuid_de_outra_empresa(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $otherTenantOwner = User::factory()->create(['email_verified_at' => now()]);
        $otherTenant = $otherTenantOwner->tenant;

        $response = $this->withHeaders($this->authHeaders($owner))
            ->postJson('/api/tenant/validate', [
                'step' => 1,
                'uuid' => $otherTenant->uuid,
                'name' => 'Nome Válido',
            ]);

        $response->assertStatus(404);
    }

    /** Multipart como o painel envia: POST com _method=PUT */
    private function sendImages(User $user, string $uuid, array $fields)
    {
        return $this->withHeaders($this->authHeaders($user))
            ->post("/api/tenant/{$uuid}", array_merge(['_method' => 'PUT'], $fields));
    }

    #[Test]
    public function envia_capa_do_cardapio(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $response = $this->sendImages($owner, $tenant->uuid, [
            'cover' => UploadedFile::fake()->image('capa.jpg', 1600, 640)->size(1200),
        ]);

        $response->assertOk();
        $cover = $tenant->fresh()->cover;
        $this->assertStringContainsString("tenants/{$tenant->uuid}/covers/", $cover);
        Storage::disk('logos')->assertExists($cover);
        $this->assertStringStartsWith('/storage/', $response->json('data.cover'));
    }

    #[Test]
    public function trocar_capa_apaga_o_arquivo_anterior(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;
        $this->sendImages($owner, $tenant->uuid, ['cover' => UploadedFile::fake()->image('a.jpg', 800, 320)])->assertOk();
        $old = $tenant->fresh()->cover;

        $this->sendImages($owner, $tenant->uuid, ['cover' => UploadedFile::fake()->image('b.jpg', 800, 320)])->assertOk();

        $new = $tenant->fresh()->cover;
        $this->assertNotSame($old, $new);
        Storage::disk('logos')->assertMissing($old);
        Storage::disk('logos')->assertExists($new);
    }

    #[Test]
    public function remover_capa_limpa_o_campo_e_apaga_o_arquivo(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;
        $this->sendImages($owner, $tenant->uuid, ['cover' => UploadedFile::fake()->image('a.jpg', 800, 320)])->assertOk();
        $old = $tenant->fresh()->cover;

        $this->sendImages($owner, $tenant->uuid, ['remove_cover' => '1'])->assertOk();

        $this->assertNull($tenant->fresh()->cover);
        Storage::disk('logos')->assertMissing($old);
    }

    #[Test]
    public function capa_invalida_retorna_422_e_mantem_a_atual(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;
        $this->sendImages($owner, $tenant->uuid, ['cover' => UploadedFile::fake()->image('a.jpg', 800, 320)])->assertOk();
        $current = $tenant->fresh()->cover;

        $this->sendImages($owner, $tenant->uuid, ['cover' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf')])
            ->assertStatus(422)->assertJsonValidationErrors('cover');
        $this->sendImages($owner, $tenant->uuid, ['cover' => UploadedFile::fake()->image('grande.jpg', 800, 320)->size(6000)])
            ->assertStatus(422)->assertJsonValidationErrors('cover');

        $this->assertSame($current, $tenant->fresh()->cover);
        Storage::disk('logos')->assertExists($current);
    }

    #[Test]
    public function nao_envia_capa_para_tenant_de_outra_empresa(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $otherTenant = User::factory()->create(['email_verified_at' => now()])->tenant;

        $this->sendImages($owner, $otherTenant->uuid, ['cover' => UploadedFile::fake()->image('a.jpg', 800, 320)])
            ->assertStatus(404);

        $this->assertNull($otherTenant->fresh()->cover);
    }

    #[Test]
    public function salvar_so_o_nome_mantem_a_capa(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;
        $this->sendImages($owner, $tenant->uuid, ['cover' => UploadedFile::fake()->image('a.jpg', 800, 320)])->assertOk();
        $cover = $tenant->fresh()->cover;

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/tenant/{$tenant->uuid}", ['name' => 'Outro Nome'])
            ->assertOk();

        $this->assertSame($cover, $tenant->fresh()->cover);
    }

    #[Test]
    public function logo_continua_sendo_trocado_e_removido_apagando_o_arquivo_anterior(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $this->sendImages($owner, $tenant->uuid, ['logo' => UploadedFile::fake()->image('logo1.png', 400, 400)])->assertOk();
        $first = $tenant->fresh()->logo;
        $this->assertStringContainsString("tenants/{$tenant->uuid}/logos/", $first);

        $this->sendImages($owner, $tenant->uuid, ['logo' => UploadedFile::fake()->image('logo2.png', 400, 400)])->assertOk();
        $second = $tenant->fresh()->logo;
        Storage::disk('logos')->assertMissing($first);
        Storage::disk('logos')->assertExists($second);

        $this->sendImages($owner, $tenant->uuid, ['remove_logo' => '1'])->assertOk();
        $this->assertNull($tenant->fresh()->logo);
        Storage::disk('logos')->assertMissing($second);
    }

    #[Test]
    public function logo_webp_de_3mb_e_aceito_e_reduzido_para_1024(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $this->sendImages($owner, $tenant->uuid, [
            'logo' => UploadedFile::fake()->image('logo.webp', 2000, 2000)->size(3000),
        ])->assertOk();

        $logo = $tenant->fresh()->logo;
        Storage::disk('logos')->assertExists($logo);
        [$width, $height] = getimagesize(Storage::disk('logos')->path($logo));
        $this->assertLessThanOrEqual(1024, $width);
        $this->assertLessThanOrEqual(1024, $height);
    }

    #[Test]
    public function logo_svg_ou_maior_que_5mb_retorna_422(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $this->sendImages($owner, $tenant->uuid, ['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])
            ->assertStatus(422)->assertJsonValidationErrors('logo');
        $this->sendImages($owner, $tenant->uuid, ['logo' => UploadedFile::fake()->image('logo.png', 400, 400)->size(6000)])
            ->assertStatus(422)->assertJsonValidationErrors('logo');

        $this->assertNull($tenant->fresh()->logo);
    }

    #[Test]
    public function falha_no_upload_vira_422_e_descarta_a_outra_imagem_enviada_junto(): void
    {
        Storage::fake('logos');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;
        // Logo passa pelo serviço real; a capa falha dentro do FileUploadService (ex.: limite do serviço)
        $this->partialMock(FileUploadService::class, function ($mock) {
            $mock->shouldReceive('uploadFile')->with(Mockery::any(), 'logo', Mockery::any())->passthru();
            $mock->shouldReceive('uploadFile')->with(Mockery::any(), 'cover', Mockery::any())
                ->andThrow(new \Exception('Arquivo de imagem inválido'));
        });

        $response = $this->sendImages($owner, $tenant->uuid, [
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
            'cover' => UploadedFile::fake()->image('capa.jpg', 800, 320),
        ]);

        $response->assertStatus(422)->assertJsonPath('errors.cover.0', 'Arquivo de imagem inválido');
        $this->assertNull($tenant->fresh()->logo);
        $this->assertNull($tenant->fresh()->cover);
        $this->assertSame([], Storage::disk('logos')->allFiles());
    }
}
