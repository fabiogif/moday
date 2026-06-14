<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'user_id',
        'rating',
        'comment',
        'customer_name',
        'customer_email',
        'status',
        'moderated_by',
        'moderated_at',
        'moderation_reason',
        'is_featured',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
        'helpful_count' => 'integer',
        'moderated_at' => 'datetime',
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
        
        static::creating(function ($review) {
            if (empty($review->uuid)) {
                $review->uuid = (string) Str::uuid();
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
     * Relacionamento: Pedido
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relacionamento: Usuário (Cliente)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Moderador
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Scope: Apenas avaliações aprovadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Apenas avaliações pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Apenas avaliações rejeitadas
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: Apenas avaliações em destaque
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Filtrar por tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Aprovar avaliação
     */
    public function approve($moderatorId, $reason = null)
    {
        $this->update([
            'status' => 'approved',
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Rejeitar avaliação
     */
    public function reject($moderatorId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason ?? 'Conteúdo inadequado',
        ]);

        return $this;
    }

    /**
     * Marcar como destaque
     */
    public function markAsFeatured()
    {
        $this->update(['is_featured' => true]);
        return $this;
    }

    /**
     * Desmarcar destaque
     */
    public function unmarkAsFeatured()
    {
        $this->update(['is_featured' => false]);
        return $this;
    }

    /**
     * Incrementar contador de útil
     */
    public function incrementHelpful()
    {
        $this->increment('helpful_count');
        return $this;
    }

    /**
     * Verificar se pode ser editada
     */
    public function canBeModerated(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Obter nome do cliente (user ou guest)
     */
    public function getCustomerNameAttribute()
    {
        return $this->attributes['customer_name'] ?? $this->user?->name ?? 'Cliente';
    }
}

