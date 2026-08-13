<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AccountReceivable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'accounts_receivable';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'financial_category_id',
        'client_id',
        'order_id',
        'sale_order_id',
        'payment_method_id',
        'description',
        'issue_date',
        'due_date',
        'receipt_date',
        'amount',
        'amount_received',
        'discount',
        'interest',
        'fine',
        'status',
        'document_number',
        'installment_number',
        'total_installments',
        'notes',
        'attachment_path',
        'mp_preference_id',
        'mp_payment_id',
        'mp_payment_link',
        'mp_link_generated_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'discount' => 'decimal:2',
        'interest' => 'decimal:2',
        'fine' => 'decimal:2',
        'installment_number'    => 'integer',
        'total_installments'    => 'integer',
        'mp_link_generated_at'  => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
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
     * Relacionamento com Cliente
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relacionamento com Pedido
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relacionamento com Forma de Pagamento
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'uuid');
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
     * Scope para contas recebidas
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'recebido');
    }

    /**
     * Scope para contas atrasadas
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
     * Scope para contas em um período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Scope por cliente
     */
    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Calcular valor total (amount + juros + multa - desconto)
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + ($this->interest ?? 0) + ($this->fine ?? 0) - ($this->discount ?? 0);
    }

    /**
     * Verifica se está atrasada
     */
    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['pendente', 'vencido', 'parcial']) && $this->due_date < now();
    }

    /**
     * Dias até recebimento (negativo se atrasada)
     */
    public function getDaysUntilExpectedAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }
}

