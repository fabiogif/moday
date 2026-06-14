<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'uuid',
        // B2B fields
        'cnpj', 'company_name', 'trade_name', 'state_registration',
        'whatsapp', 'contact_name', 'contact_phone',
        'payment_term_days', 'min_order_value', 'lead_time_days',
        // Legacy fields (from financial module schema)
        'name', 'fantasy_name', 'document', 'document_type',
        // Shared fields
        'email', 'phone',
        'address', 'city', 'state', 'zip_code', 'zipcode', 'neighborhood', 'number', 'complement',
        'bank_name', 'bank_agency', 'bank_account', 'pix_key',
        'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_order_value' => 'decimal:2',
            'payment_term_days' => 'integer',
            'lead_time_days' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid ??= Str::uuid());
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function accountsPayable()
    {
        return $this->hasMany(AccountPayable::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->trade_name ?? $this->company_name;
    }
}
