<?php

namespace App\Services\Visit;

use App\Models\Visit;
use App\Models\VisitMedia;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class VisitMediaService
{
    private const UPLOAD_TYPES = [
        'photo' => 'visit_photo',
        'document' => 'visit_document',
        'audio' => 'visit_audio',
    ];

    public function __construct(private readonly FileUploadService $fileUploadService)
    {
    }

    public function list(Visit $visit): Collection
    {
        return $visit->media()->orderByDesc('created_at')->get();
    }

    public function store(Visit $visit, string $type, UploadedFile $file, int $uploadedByUserId): VisitMedia
    {
        $tenant = $visit->tenant;
        $uploaded = $this->fileUploadService->uploadFile($file, self::UPLOAD_TYPES[$type], $tenant->uuid);

        return VisitMedia::create([
            'tenant_id' => $visit->tenant_id,
            'visit_id' => $visit->id,
            'type' => $type,
            'file_path' => $uploaded['path'],
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $uploaded['mime_type'],
            'size_bytes' => $uploaded['size'],
            'uploaded_by_user_id' => $uploadedByUserId,
        ]);
    }
}
