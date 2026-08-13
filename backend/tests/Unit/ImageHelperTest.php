<?php

namespace Tests\Unit;

use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ImageHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', 'http://163.176.233.174');

        $publicDisk = [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => 'http://163.176.233.174/storage',
            'visibility' => 'public',
        ];

        Config::set('filesystems.default', 'public');
        Config::set('filesystems.disks.public', $publicDisk);
        Config::set('filesystems.disks.products', array_merge($publicDisk, [
            'root' => storage_path('app/public/products'),
            'url' => 'http://163.176.233.174/storage/products',
        ]));
    }

    public function test_product_path_uses_products_disk_prefix(): void
    {
        $url = ImageHelper::processImageUrl('tenants/abc/products/photo.jpg');

        $this->assertSame(
            'http://163.176.233.174/storage/products/tenants/abc/products/photo.jpg',
            $url
        );
    }

    public function test_rewrites_wrong_public_url_to_products_disk(): void
    {
        $stored = 'http://163.176.233.174/storage/tenants/abc/products/photo.jpg';

        $url = ImageHelper::processImageUrl($stored);

        $this->assertSame(
            'http://163.176.233.174/storage/products/tenants/abc/products/photo.jpg',
            $url
        );
    }

    public function test_rewrites_localhost_url_to_current_app_url(): void
    {
        $stored = 'http://localhost/storage/tenants/abc/products/photo.jpg';

        $url = ImageHelper::processImageUrl($stored);

        $this->assertNotNull($url);
        $this->assertStringNotContainsString('localhost', $url);
        $this->assertStringContainsString('/storage/products/tenants/abc/products/photo.jpg', $url);
    }

    public function test_normalize_to_storage_path_extracts_from_absolute_url(): void
    {
        $path = ImageHelper::normalizeToStoragePath(
            'http://localhost/storage/tenants/abc/products/photo.jpg'
        );

        $this->assertSame('tenants/abc/products/photo.jpg', $path);
    }

    public function test_public_asset_path_returns_relative_storage_url(): void
    {
        $path = ImageHelper::publicAssetPath('tenants/abc/products/photo.jpg');

        $this->assertSame(
            '/storage/products/tenants/abc/products/photo.jpg',
            $path
        );
    }

    public function test_public_asset_path_fixes_legacy_storage_url_without_products_prefix(): void
    {
        $path = ImageHelper::publicAssetPath(
            'http://localhost/storage/tenants/abc/products/photo.jpg'
        );

        $this->assertSame(
            '/storage/products/tenants/abc/products/photo.jpg',
            $path
        );
    }
}
