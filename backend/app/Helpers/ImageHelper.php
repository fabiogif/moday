<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Extrai o caminho relativo dentro do disco a partir de URL absoluta ou path parcial.
     */
    public static function normalizeToStoragePath(?string $image): ?string
    {
        if (!$image) {
            return null;
        }

        $image = trim($image);

        if ($image === '') {
            return null;
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $path = parse_url($image, PHP_URL_PATH) ?? '';

            return self::normalizePublicStoragePath($path);
        }

        return self::normalizePublicStoragePath($image);
    }

    /**
     * Processa URL de imagem corretamente, regenerando com APP_URL/FILESYSTEM_PUBLIC_URL atuais.
     */
    public static function processImageUrl(?string $image, string $disk = 'public'): ?string
    {
        $storagePath = self::normalizeToStoragePath($image);

        if (!$storagePath) {
            return null;
        }

        $diskName = self::resolveDisk($disk, $storagePath);

        return \Storage::disk($diskName)->url($storagePath);
    }

    /**
     * Caminho público relativo (/storage/...) sem host — ideal para APIs consumidas pelo frontend.
     */
    public static function publicAssetPath(?string $image, string $disk = 'public'): ?string
    {
        $url = self::processImageUrl($image, $disk);

        if (!$url) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $path = parse_url($url, PHP_URL_PATH);

            return $path ?: null;
        }

        return str_starts_with($url, '/') ? $url : '/' . $url;
    }

    /**
     * Gera URL completa para upload de imagem
     */
    public static function generateImageUrl(string $imagePath, string $disk = 'public'): string
    {
        $storagePath = self::normalizeToStoragePath($imagePath);

        if (!$storagePath) {
            return '';
        }

        $diskName = self::resolveDisk($disk, $storagePath);

        return \Storage::disk($diskName)->url($storagePath);
    }

    private static function normalizePublicStoragePath(string $path): string
    {
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/products/')) {
            return substr($path, strlen('storage/products/'));
        }

        if (str_starts_with($path, 'storage/logos/')) {
            return substr($path, strlen('storage/logos/'));
        }

        // URL legada: /storage/tenants/{uuid}/products/...
        if (preg_match('#^storage/tenants/[^/]+/products/#', $path)) {
            return substr($path, strlen('storage/'));
        }

        if (preg_match('#^storage/tenants/[^/]+/logos/#', $path)) {
            return substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
        }

        return $path;
    }

    private static function resolveDisk(?string $preferredDisk, ?string $path): string
    {
        // Paths de produto/logotipo têm disco dedicado — prioridade sobre o disco padrão "public"
        if (self::isProductStoragePath($path) && config('filesystems.disks.products')) {
            return 'products';
        }

        if (self::isLogoStoragePath($path) && config('filesystems.disks.logos')) {
            return 'logos';
        }

        if ($preferredDisk && $preferredDisk !== 'public' && config("filesystems.disks.{$preferredDisk}")) {
            return $preferredDisk;
        }

        return config('filesystems.default', 'public');
    }

    private static function isProductStoragePath(?string $path): bool
    {
        return $path && (bool) preg_match('#(^|/)tenants/[^/]+/products/#', $path);
    }

    private static function isLogoStoragePath(?string $path): bool
    {
        return $path && (bool) preg_match('#(^|/)tenants/[^/]+/logos/#', $path);
    }
}
