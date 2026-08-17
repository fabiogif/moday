<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PDVFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pdv_feedbacks';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'type',
        'rating',
        'message',
        'quick_emoji',
        'metadata',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'rating' => 'integer',
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($feedback) {
            if (empty($feedback->uuid)) {
                $feedback->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relacionamento: Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento: Usuário que enviou o feedback
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Usuário que revisou o feedback
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope: Feedbacks pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Feedbacks por tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Feedbacks por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Feedbacks com rating
     */
    public function scopeWithRating($query)
    {
        return $query->whereNotNull('rating');
    }
}

