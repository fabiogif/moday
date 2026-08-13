<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VisitMedia extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;
    public const TYPES = ['photo', 'document', 'audio'];

    protected $fillable = [
        'tenant_id', 'uuid', 'visit_id', 'type', 'file_path', 'file_name', 'mime_type', 'size_bytes', 'uploaded_by_user_id',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (self $media) {
            $media->uuid ??= (string) Str::uuid();
        });
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
