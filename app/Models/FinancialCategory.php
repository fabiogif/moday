<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FinancialCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'type',
        'applies_to_payable',
        'applies_to_receivable',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'applies_to_payable' => 'boolean',
        'applies_to_receivable' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Relacionamento com Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com Despesas
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Relacionamento com Contas a Pagar
     */
    public function accountsPayable()
    {
        return $this->hasMany(AccountPayable::class);
    }

    /**
     * Relacionamento com Contas a Receber
     */
    public function accountsReceivable()
    {
        return $this->hasMany(AccountReceivable::class);
    }

    /**
     * Scope para categorias ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para categorias de um tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para categorias por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

