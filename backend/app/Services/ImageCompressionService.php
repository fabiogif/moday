<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressionService
{
    private const MAX_DIMENSION = 1920;
    private const JPEG_QUALITY = 82;
    private const PNG_COMPRESSION = 6;
    private const WEBP_QUALITY = 82;

    private const SUPPORTED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Compress (resize + re-encode) an uploaded image and store it.
     * Falls back to a plain store() for formats GD can't handle (e.g. SVG)
     * or if the image resource can't be read.
     */
    public function compressAndStore(UploadedFile $file, string $directory, ?string $disk = null): string
    {
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            return $file->store($directory, $disk);
        }

        $image = $this->createImageResource($file->getRealPath(), $mimeType);

        if ($image === null || $image === false) {
            return $file->store($directory, $disk);
        }

        $image = $this->resizeIfNeeded($image);

        $extension = $this->extensionForMime($mimeType);
        $path = trim($directory, '/') . '/' . Str::random(40) . '.' . $extension;

        $tmpFile = tempnam(sys_get_temp_dir(), 'img_compress_');
        $this->encodeImage($image, $mimeType, $tmpFile);
        imagedestroy($image);

        Storage::disk($disk ?? config('filesystems.default'))
            ->put($path, file_get_contents($tmpFile));

        unlink($tmpFile);

        return $path;
    }

    /**
     * @return \GdImage|false|null
     */
    private function createImageResource(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }

    private function resizeIfNeeded(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= self::MAX_DIMENSION && $height <= self::MAX_DIMENSION) {
            return $image;
        }

        $ratio = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/GIF/WebP
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    private function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function encodeImage(\GdImage $image, string $mimeType, string $path): void
    {
        match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, self::JPEG_QUALITY),
            'image/png' => imagepng($image, $path, self::PNG_COMPRESSION),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, self::WEBP_QUALITY),
            default => imagejpeg($image, $path, self::JPEG_QUALITY),
        };
    }
}
