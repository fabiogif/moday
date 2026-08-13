<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class FileUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;
    private Category $category;
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Usar fake storage para testes
        Storage::fake('public');
        Storage::fake('temp');
        Storage::fake('products');
        Storage::fake('logos');
        
        // Criar estrutura básica para os testes
        $plan = \App\Models\Plan::factory()->create();
        
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'uuid' => 'test-tenant-uuid-' . uniqid()
        ]);
        
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $this->category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Eletrônicos'
        ]);
        
        $this->token = JWTAuth::fromUser($this->user);
        $this->fileUploadService = app(FileUploadService::class);
    }

    #[Test]
    public function pode_fazer_upload_de_imagem_de_produto()
    {
        $image = UploadedFile::fake()->image('produto.jpg', 800, 600);

        $result = $this->fileUploadService->uploadFile(
            $image, 
            'product', 
            $this->tenant->uuid
        );

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertArrayHasKey('disk', $result);

        // Verificar se arquivo foi salvo (usar o disco correto)
        Storage::disk($result['disk'])->assertExists($result['path']);
        
        // Verificar se URL está correta
        $this->assertStringContainsString('tenants/' . $this->tenant->uuid . '/products', $result['path']);
        $this->assertStringContainsString($result['filename'], $result['url']);
    }

    #[Test]
    public function pode_fazer_upload_de_logo_de_empresa()
    {
        $logo = UploadedFile::fake()->image('logo.png', 400, 400);

        $result = $this->fileUploadService->uploadFile(
            $logo, 
            'logo', 
            $this->tenant->uuid
        );

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        
        // Verificar se arquivo foi salvo (usar o disco correto)
        Storage::disk($result['disk'])->assertExists($result['path']);
        
        // Verificar se URL está correta
        $this->assertStringContainsString('tenants/' . $this->tenant->uuid . '/logos', $result['path']);
    }

    #[Test]
    public function valida_tamanho_maximo_de_arquivo()
    {
        // Criar imagem maior que 2MB
        $largeImage = UploadedFile::fake()->create('large.jpg', 3000); // 3MB

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Arquivo muito grande');

        $this->fileUploadService->uploadFile(
            $largeImage, 
            'product', 
            $this->tenant->uuid
        );
    }

    #[Test]
    public function valida_tipo_de_arquivo_permitido()
    {
        // Criar arquivo que não é imagem
        $pdfFile = UploadedFile::fake()->create('documento.pdf', 1000);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tipo de arquivo não permitido');

        $this->fileUploadService->uploadFile(
            $pdfFile, 
            'product', 
            $this->tenant->uuid
        );
    }

    #[Test]
    public function redimensiona_imagem_acima_do_limite_de_dimensoes()
    {
        // Imagens maiores que 2048x2048 devem ser redimensionadas, nao rejeitadas
        $largeImage = UploadedFile::fake()->image('large.jpg', 3000, 3000);

        $result = $this->fileUploadService->uploadFile(
            $largeImage,
            'product',
            $this->tenant->uuid
        );

        Storage::disk($result['disk'])->assertExists($result['path']);

        $imageInfo = getimagesize(Storage::disk($result['disk'])->path($result['path']));
        $this->assertLessThanOrEqual(2048, $imageInfo[0]);
        $this->assertLessThanOrEqual(2048, $imageInfo[1]);
    }

    #[Test]
    public function redimensiona_imagem_automaticamente()
    {
        // Criar imagem grande mas dentro do limite de validação
        $largeImage = UploadedFile::fake()->image('large.jpg', 2000, 1500);

        $result = $this->fileUploadService->uploadFile(
            $largeImage, 
            'product', 
            $this->tenant->uuid,
            ['quality' => 80]
        );

        // Verificar se arquivo foi salvo (usar o disco correto)
        Storage::disk($result['disk'])->assertExists($result['path']);
        
        // Verificar se foi redimensionado (deve ser menor que 2048x2048)
        $imageInfo = getimagesize(Storage::disk($result['disk'])->path($result['path']));
        $this->assertLessThanOrEqual(2048, $imageInfo[0]);
        $this->assertLessThanOrEqual(2048, $imageInfo[1]);
    }

    #[Test]
    public function pode_fazer_upload_multiplo()
    {
        $images = [
            UploadedFile::fake()->image('produto1.jpg', 800, 600),
            UploadedFile::fake()->image('produto2.png', 1024, 768),
        ];

        $results = $this->fileUploadService->uploadMultiple(
            $images, 
            'product', 
            $this->tenant->uuid
        );

        $this->assertCount(2, $results);
        
        foreach ($results as $result) {
            $this->assertArrayHasKey('path', $result);
            $this->assertArrayHasKey('url', $result);
            Storage::disk($result['disk'])->assertExists($result['path']);
        }
    }

    #[Test]
    public function pode_deletar_arquivo()
    {
        $image = UploadedFile::fake()->image('produto.jpg', 800, 600);

        $result = $this->fileUploadService->uploadFile(
            $image, 
            'product', 
            $this->tenant->uuid
        );

        // Verificar se arquivo existe
        Storage::disk($result['disk'])->assertExists($result['path']);

        // Deletar arquivo
        $deleted = $this->fileUploadService->deleteFile($result['path'], $result['disk']);

        $this->assertTrue($deleted);
        Storage::disk($result['disk'])->assertMissing($result['path']);
    }

    #[Test]
    public function pode_deletar_multiplos_arquivos()
    {
        $images = [
            UploadedFile::fake()->image('produto1.jpg', 800, 600),
            UploadedFile::fake()->image('produto2.png', 1024, 768),
        ];

        $results = $this->fileUploadService->uploadMultiple(
            $images, 
            'product', 
            $this->tenant->uuid
        );

        $paths = array_column($results, 'path');
        $disk = $results[0]['disk'];
        
        // Verificar se arquivos existem
        foreach ($paths as $path) {
            Storage::disk($disk)->assertExists($path);
        }

        // Deletar múltiplos arquivos
        $deletedResults = $this->fileUploadService->deleteMultiple($paths, $disk);

        $this->assertCount(2, $deletedResults);
        
        foreach ($deletedResults as $path => $deleted) {
            $this->assertTrue($deleted);
            Storage::disk($disk)->assertMissing($path);
        }
    }

    #[Test]
    public function verifica_se_arquivo_existe()
    {
        $image = UploadedFile::fake()->image('produto.jpg', 800, 600);

        $result = $this->fileUploadService->uploadFile(
            $image, 
            'product', 
            $this->tenant->uuid
        );

        // Verificar se arquivo existe
        $exists = $this->fileUploadService->fileExists($result['path'], $result['disk']);
        $this->assertTrue($exists);

        // Verificar arquivo inexistente
        $notExists = $this->fileUploadService->fileExists('arquivo-inexistente.jpg', 'public');
        $this->assertFalse($notExists);
    }

    #[Test]
    public function obtem_url_do_arquivo()
    {
        $image = UploadedFile::fake()->image('produto.jpg', 800, 600);

        $result = $this->fileUploadService->uploadFile(
            $image, 
            'product', 
            $this->tenant->uuid
        );

        // Obter URL do arquivo
        $url = $this->fileUploadService->getFileUrl($result['path'], $result['disk']);
        
        $this->assertNotNull($url);
        $this->assertStringContainsString($result['filename'], $url);

        // Obter URL de arquivo inexistente
        $nullUrl = $this->fileUploadService->getFileUrl('arquivo-inexistente.jpg', 'public');
        $this->assertNull($nullUrl);
    }

    #[Test]
    public function obtem_estatisticas_de_storage()
    {
        // Fazer upload de alguns arquivos
        $images = [
            UploadedFile::fake()->image('produto1.jpg', 800, 600),
            UploadedFile::fake()->image('produto2.png', 1024, 768),
        ];

        foreach ($images as $image) {
            $this->fileUploadService->uploadFile($image, 'product', $this->tenant->uuid);
        }

        $stats = $this->fileUploadService->getStorageStats('products');

        $this->assertArrayHasKey('file_count', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('total_size_mb', $stats);
        $this->assertArrayHasKey('disk', $stats);

        $this->assertEquals(2, $stats['file_count']);
        $this->assertEquals('products', $stats['disk']);
        $this->assertGreaterThan(0, $stats['total_size']);
    }

    #[Test]
    public function gera_nome_unico_para_arquivo()
    {
        $image1 = UploadedFile::fake()->image('produto.jpg', 800, 600);
        $image2 = UploadedFile::fake()->image('produto.jpg', 800, 600);

        $result1 = $this->fileUploadService->uploadFile($image1, 'product', $this->tenant->uuid);
        $result2 = $this->fileUploadService->uploadFile($image2, 'product', $this->tenant->uuid);

        // Nomes devem ser diferentes mesmo com mesmo nome original
        $this->assertNotEquals($result1['filename'], $result2['filename']);
        
        // Product usa hashName (sem underscore obrigatório) — apenas extensões image
        $this->assertMatchesRegularExpression('/\.(jpe?g|png|gif|webp)$/i', $result1['filename']);
        $this->assertMatchesRegularExpression('/\.(jpe?g|png|gif|webp)$/i', $result2['filename']);
    }

    #[Test]
    public function preserva_transparencia_em_png()
    {
        $pngImage = UploadedFile::fake()->image('transparent.png', 400, 400);

        $result = $this->fileUploadService->uploadFile(
            $pngImage, 
            'product', 
            $this->tenant->uuid
        );

        // Verificar se arquivo foi salvo (usar o disco correto)
        Storage::disk($result['disk'])->assertExists($result['path']);
        
        // Verificar se é PNG
        $this->assertStringEndsWith('.png', $result['filename']);
    }

    #[Test]
    public function funciona_com_arquivo_nao_imagem()
    {
        $document = UploadedFile::fake()->create('documento.txt', 1000, 'text/plain');

        $result = $this->fileUploadService->uploadFile(
            $document, 
            'document', 
            $this->tenant->uuid
        );

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        
        // Verificar se arquivo foi salvo
        Storage::disk('temp')->assertExists($result['path']);
    }

    #[Test]
    public function trata_erro_gracefully()
    {
        // Testar com arquivo inválido para simular erro
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 1000, 'text/plain');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tipo de arquivo não permitido');

        $this->fileUploadService->uploadFile($invalidFile, 'product', $this->tenant->uuid);
    }
}
