<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruneTenantImagesCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('logos');
        $this->tenant = Tenant::factory()->create();

        $base = "tenants/{$this->tenant->uuid}";
        foreach (["{$base}/logos/atual.png", "{$base}/logos/antigo.png", "{$base}/covers/atual.jpg", "{$base}/covers/antiga.jpg", "{$base}/outros/arquivo.txt"] as $path) {
            Storage::disk('logos')->put($path, 'x');
            touch(Storage::disk('logos')->path($path), now()->subDays(3)->timestamp);
        }

        // Logo gravado como caminho relativo; capa como URL pública (formato legado também deve casar)
        $this->tenant->forceFill([
            'logo' => "{$base}/logos/atual.png",
            'cover' => "/storage/logos/{$base}/covers/atual.jpg",
        ])->save();
    }

    #[Test]
    public function sem_delete_so_relata_e_nao_apaga_nada(): void
    {
        $base = "tenants/{$this->tenant->uuid}";

        $this->artisan('files:prune-tenant-images')
            ->expectsOutputToContain("{$base}/logos/antigo.png")
            ->expectsOutputToContain("{$base}/covers/antiga.jpg")
            ->doesntExpectOutputToContain("{$base}/logos/atual.png")
            ->expectsOutputToContain('Nada foi apagado')
            ->assertSuccessful();

        Storage::disk('logos')->assertExists("{$base}/logos/antigo.png");
        Storage::disk('logos')->assertExists("{$base}/covers/antiga.jpg");
    }

    #[Test]
    public function com_delete_apaga_so_as_imagens_sem_uso(): void
    {
        $base = "tenants/{$this->tenant->uuid}";

        $this->artisan('files:prune-tenant-images --delete')->assertSuccessful();

        Storage::disk('logos')->assertMissing("{$base}/logos/antigo.png");
        Storage::disk('logos')->assertMissing("{$base}/covers/antiga.jpg");
        Storage::disk('logos')->assertExists("{$base}/logos/atual.png");
        Storage::disk('logos')->assertExists("{$base}/covers/atual.jpg");
        Storage::disk('logos')->assertExists("{$base}/outros/arquivo.txt");
    }

    #[Test]
    public function ignora_arquivos_recentes_que_podem_ser_upload_em_andamento(): void
    {
        $recent = "tenants/{$this->tenant->uuid}/covers/enviando.jpg";
        Storage::disk('logos')->put($recent, 'x');

        $this->artisan('files:prune-tenant-images --delete')->assertSuccessful();

        Storage::disk('logos')->assertExists($recent);
    }
}
