<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AccountPayable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'accounts_payable';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'financial_category_id',
        'supplier_id',
        'purchase_order_id',
        'payment_method_id',
        'expense_id',
        'description',
        'issue_date',
        'due_date',
        'payment_date',
        'amount',
        'amount_paid',
        'discount',
        'interest',
        'fine',
        'status',
        'document_number',
        'installment_number',
        'total_installments',
        'notes',
        'attachment_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'discount' => 'decimal:2',
        'interest' => 'decimal:2',
        'fine' => 'decimal:2',
        'installment_number' => 'integer',
        'total_installments' => 'integer',
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

        // Atualizar status automaticamente para "vencido"
        static::saving(function ($model) {
            if ($model->status === 'pendente' && $model->due_date < now()) {
                $model->status = 'vencido';
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
     * Relacionamento com Categoria
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
     * Relacionamento com Despesa
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Scope para contas de um tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para contas pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pendente');
    }

    /**
     * Scope para contas pagas
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'pago');
    }

    /**
     * Scope para contas vencidas
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'vencido')
            ->orWhere(function($q) {
                $q->where('status', 'pendente')
                  ->where('due_date', '<', now());
            });
    }

    /**
     * Scope para contas próximas do vencimento
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', 'pendente')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope para contas em um período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Calcular valor total (amount + juros + multa - desconto)
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + ($this->interest ?? 0) + ($this->fine ?? 0) - ($this->discount ?? 0);
    }

    /**
     * Verifica se está vencida
     */
    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['pendente', 'vencido', 'parcial']) && $this->due_date < now();
    }

    /**
     * Dias até o vencimento (negativo se vencida)
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }
}

