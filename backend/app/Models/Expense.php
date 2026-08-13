<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'financial_category_id',
        'supplier_id',
        'payment_method_id',
        'description',
        'issue_date',
        'due_date',
        'payment_date',
        'amount',
        'status',
        'recurrence',
        'notes',
        'attachment_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
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
     * Relacionamento com Categoria Financeira
     */
    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'financial_category_id');
    }

    /**
     * Relacionamento com Fornecedor
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relacionamento com Forma de Pagamento
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'uuid');
    }

    /**
     * Scope para despesas de um tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para despesas pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pendente');
    }

    /**
     * Scope para despesas pagas
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'pago');
    }

    /**
     * Scope para despesas em um período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Scope para despesas por categoria
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('financial_category_id', $categoryId);
    }

    /**
     * Verifica se a despesa está vencida
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pendente' && $this->due_date < now();
    }

    /**
     * Dias até o vencimento
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }
}

