<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    /**
     * Configurações de upload por tipo
     */
    private const UPLOAD_CONFIGS = [
        'product' => [
            'disk' => 'products',
            'max_size' => 2048, // 2MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'max_width' => 2048,
            'max_height' => 2048,
            'quality' => 85,
            'path_prefix' => 'tenants/{tenant_uuid}/products',
            'use_hash_name' => true,
            'visibility' => 'public',
        ],
        'logo' => [
            'disk' => 'logos',
            'max_size' => 1024, // 1MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'svg'],
            'max_width' => 1024,
            'max_height' => 1024,
            'quality' => 90,
            'path_prefix' => 'tenants/{tenant_uuid}/logos',
        ],
        'document' => [
            'disk' => 'temp',
            'max_size' => 10240, // 10MB
            'allowed_types' => ['pdf', 'doc', 'docx', 'txt'],
            'path_prefix' => 'documents',
        ],
        'pod' => [
            'disk' => 'public',
            'max_size' => 5120, // 5MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_width' => 2048,
            'max_height' => 2048,
            'quality' => 80,
            'path_prefix' => 'pod/{tenant_uuid}',
            'use_hash_name' => true,
            'visibility' => 'public',
        ],
    ];

    /**
     * Upload de arquivo com validação e otimização
     */
    public function uploadFile(UploadedFile $file, string $type, string $tenantUuid, array $options = []): array
    {
        $config = $this->getConfig($type);
        
        // Validações
        $this->validateFile($file, $config);
        
        // Gerar nome único
        $filename = $this->generateFilename($file, $config, $options);
        
        // Processar arquivo (compressão, redimensionamento)
        $processedFile = $this->processFile($file, $config, $options);
        
        // Definir caminho
        $path = $this->buildPath($config['path_prefix'], $tenantUuid, $filename);
        
        // Resolver disco efetivo (fallback para public se necessário)
        $storageDiskName = config("filesystems.disks.{$config['disk']}") ? $config['disk'] : 'public';

        // Upload
        $uploadedPath = $this->performUpload($processedFile, $path, $storageDiskName, $config, $options);
        
        // Gerar URL
        $storageDisk = Storage::disk($storageDiskName);
        $diskConfig = config("filesystems.disks.{$storageDiskName}", []);
        $baseUrl = $diskConfig['url'] ?? (rtrim(config('app.url'), '/') . '/storage');
        $url = rtrim($baseUrl, '/') . '/' . ltrim($uploadedPath, '/');
        
        return [
            'path' => $uploadedPath,
            'url' => $url,
            'filename' => $filename,
            'size' => $processedFile->getSize(),
            'mime_type' => $processedFile->getMimeType(),
            'disk' => $storageDiskName,
        ];
    }

    /**
     * Upload múltiplo de arquivos
     */
    public function uploadMultiple(array $files, string $type, string $tenantUuid, array $options = []): array
    {
        $results = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = $this->uploadFile($file, $type, $tenantUuid, $options);
            }
        }
        
        return $results;
    }

    /**
     * Deletar arquivo
     */
    public function deleteFile(string $path, string $disk = 'public'): bool
    {
        try {
            $actualDisk = config("filesystems.disks.{$disk}") ? $disk : 'public';
            return Storage::disk($actualDisk)->delete($path);
        } catch (Exception $e) {
            Log::error("Erro ao deletar arquivo: {$e->getMessage()}", [
                'path' => $path,
                'disk' => $disk,
            ]);
            return false;
        }
    }

    /**
     * Deletar arquivos múltiplos
     */
    public function deleteMultiple(array $paths, string $disk = 'public'): array
    {
        $results = [];
        
        foreach ($paths as $path) {
            $results[$path] = $this->deleteFile($path, $disk);
        }
        
        return $results;
    }

    /**
     * Verificar se arquivo existe
     */
    public function fileExists(string $path, string $disk = 'public'): bool
    {
        $actualDisk = config("filesystems.disks.{$disk}") ? $disk : 'public';
        return Storage::disk($actualDisk)->exists($path);
    }

    /**
     * Obter URL do arquivo
     */
    public function getFileUrl(string $path, string $disk = 'public'): ?string
    {
        $actualDisk = config("filesystems.disks.{$disk}") ? $disk : 'public';
        
        if (!$this->fileExists($path, $actualDisk)) {
            return null;
        }

        $diskConfig = config("filesystems.disks.{$actualDisk}", []);
        $baseUrl = $diskConfig['url'] ?? (rtrim(config('app.url'), '/') . '/storage');

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Obter configuração por tipo
     */
    private function getConfig(string $type): array
    {
        if (!isset(self::UPLOAD_CONFIGS[$type])) {
            throw new Exception("Tipo de upload '{$type}' não suportado");
        }
        
        return self::UPLOAD_CONFIGS[$type];
    }

    /**
     * Validar arquivo
     */
    private function validateFile(UploadedFile $file, array $config): void
    {
        // Validar tamanho
        if ($file->getSize() > $config['max_size'] * 1024) {
            throw new Exception("Arquivo muito grande. Máximo: {$config['max_size']}KB");
        }
        
        // Validar tipo
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $config['allowed_types'])) {
            throw new Exception("Tipo de arquivo não permitido. Permitidos: " . implode(', ', $config['allowed_types']));
        }
        
        // Validar se é imagem válida
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw new Exception("Arquivo de imagem inválido");
            }
            
            // Validar dimensões se especificadas
            if (isset($config['max_width']) && $imageInfo[0] > $config['max_width']) {
                throw new Exception("Dimensões da imagem muito grandes. Máximo: {$config['max_width']}x{$config['max_height']}px");
            }
            
            if (isset($config['max_height']) && $imageInfo[1] > $config['max_height']) {
                throw new Exception("Dimensões da imagem muito grandes. Máximo: {$config['max_width']}x{$config['max_height']}px");
            }
        }
    }

    /**
     * Gerar nome único para arquivo
     */
    private function generateFilename(UploadedFile $file, array $config, array $options = []): string
    {
        $useHashName = $options['use_hash_name'] ?? ($config['use_hash_name'] ?? false);
        $prefix = $options['prefix'] ?? ($config['filename_prefix'] ?? '');

        if ($useHashName) {
            $hashName = $file->hashName();
            return $this->applyPrefix($hashName, $prefix);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(16);
        $filename = $timestamp . '_' . $random . '.' . $extension;

        return $this->applyPrefix($filename, $prefix);
    }

    private function applyPrefix(string $filename, string $prefix): string
    {
        if (empty($prefix)) {
            return ltrim($filename, '/');
        }

        $cleanPrefix = trim($prefix, '/');

        return $cleanPrefix . '/' . ltrim($filename, '/');
    }

    /**
     * Processar arquivo (compressão, redimensionamento usando GD nativo)
     */
    private function processFile(UploadedFile $file, array $config, array $options = []): UploadedFile
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Se não é imagem, retornar arquivo original
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return $file;
        }
        
        try {
            // Verificar se GD está disponível
            if (!extension_loaded('gd')) {
                Log::warning("Extensão GD não disponível, usando arquivo original");
                return $file;
            }
            
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                return $file;
            }
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            if (!$this->gdSupportsMimeType($mimeType)) {
                Log::warning("GD sem suporte a {$mimeType}, usando arquivo original");
                return $file;
            }
            
            // Verificar se precisa redimensionar
            $needsResize = false;
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
            
            if (isset($config['max_width']) && $originalWidth > $config['max_width']) {
                $needsResize = true;
                $newWidth = $config['max_width'];
                $newHeight = intval(($originalHeight * $newWidth) / $originalWidth);
            }
            
            if (isset($config['max_height']) && $newHeight > $config['max_height']) {
                $needsResize = true;
                $newHeight = $config['max_height'];
                $newWidth = intval(($originalWidth * $newHeight) / $originalHeight);
            }
            
            // Se não precisa redimensionar e não tem configuração de qualidade, retornar original
            if (!$needsResize && !isset($config['quality'])) {
                return $file;
            }
            
            // Carregar imagem original
            $sourceImage = $this->createImageFromFile($file->getPathname(), $mimeType);
            if (!$sourceImage) {
                return $file;
            }
            
            // Criar nova imagem
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparência para PNG e GIF
            if ($extension === 'png' || $extension === 'gif') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefill($newImage, 0, 0, $transparent);
            }
            
            // Redimensionar
            imagecopyresampled(
                $newImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
            
            // Salvar arquivo processado
            $tempPath = storage_path('app/temp/' . Str::uuid() . '.' . $extension);
            $this->saveImageToFile($newImage, $tempPath, $mimeType, $config['quality'] ?? 85);
            
            // Limpar memória
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            
            // Criar novo UploadedFile
            return new UploadedFile(
                $tempPath,
                $file->getClientOriginalName(),
                $file->getMimeType(),
                null,
                true
            );
            
        } catch (Exception $e) {
            Log::warning("Erro ao processar imagem: {$e->getMessage()}");
            return $file; // Retornar arquivo original em caso de erro
        }
    }
    
    /**
     * Criar resource de imagem a partir do arquivo
     */
    private function gdSupportsMimeType(string $mimeType): bool
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg'),
            'image/png' => function_exists('imagecreatefrompng'),
            'image/gif' => function_exists('imagecreatefromgif'),
            'image/webp' => function_exists('imagecreatefromwebp'),
            default => false,
        };
    }

    private function createImageFromFile(string $path, string $mimeType)
    {
        if (!$this->gdSupportsMimeType($mimeType)) {
            return false;
        }

        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }
    
    /**
     * Salvar resource de imagem em arquivo
     */
    private function saveImageToFile($image, string $path, string $mimeType, int $quality = 85): bool
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagejpeg') ? imagejpeg($image, $path, $quality) : false,
            'image/png' => function_exists('imagepng')
                ? imagepng($image, $path, (int) (9 - ($quality / 100) * 9))
                : false,
            'image/gif' => function_exists('imagegif') ? imagegif($image, $path) : false,
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            default => false,
        };
    }

    /**
     * Construir caminho do arquivo
     */
    private function buildPath(string $pathPrefix, string $tenantUuid, string $filename): string
    {
        $path = trim(str_replace('{tenant_uuid}', $tenantUuid, $pathPrefix), '/');
        $cleanFilename = ltrim($filename, '/');

        return $path !== '' ? $path . '/' . $cleanFilename : $cleanFilename;
    }

    /**
     * Realizar upload do arquivo
     */
    private function performUpload(UploadedFile $file, string $path, string $disk, array $config = [], array $options = []): string
    {
        // Para discos específicos (products, logos), usar o disco 'public' como fallback
        $actualDisk = $disk;
        if (!config("filesystems.disks.{$disk}")) {
            $actualDisk = 'public';
        }

        $storage = Storage::disk($actualDisk);

        $storageOptions = $config['storage_options'] ?? [];
        $visibility = $options['visibility'] ?? ($config['visibility'] ?? null);
        if ($visibility) {
            $storageOptions['visibility'] = $visibility;
        }

        if (!empty($options['storage_options'])) {
            $storageOptions = array_merge($storageOptions, $options['storage_options']);
        }

        if (!empty($storageOptions)) {
            $uploadedPath = $storage->putFileAs(
                dirname($path),
                $file,
                basename($path),
                $storageOptions
            );
        } else {
            $uploadedPath = $storage->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );
        }
        
        if (!$uploadedPath) {
            throw new Exception("Erro ao fazer upload do arquivo");
        }
        
        // Limpar arquivo temporário se existir
        if ($file->getPathname() !== $file->getRealPath()) {
            @unlink($file->getPathname());
        }
        
        return $uploadedPath;
    }

    /**
     * Obter estatísticas de uso de storage
     */
    public function getStorageStats(string $disk = 'public'): array
    {
        try {
            // Usar disco 'public' se o disco especificado não existir
            $actualDisk = config("filesystems.disks.{$disk}") ? $disk : 'public';
            
            $files = Storage::disk($actualDisk)->allFiles();
            $totalSize = 0;
            $fileCount = count($files);
            
            foreach ($files as $file) {
                $totalSize += Storage::disk($actualDisk)->size($file);
            }
            
            return [
                'file_count' => $fileCount,
                'total_size' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'disk' => $actualDisk,
            ];
        } catch (Exception $e) {
            Log::error("Erro ao obter estatísticas de storage: {$e->getMessage()}");
            return [
                'file_count' => 0,
                'total_size' => 0,
                'total_size_mb' => 0,
                'disk' => $disk,
            ];
        }
    }
}
