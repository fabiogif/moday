<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'plan_id', 'mp_subscription_id', 'mp_payment_id',
        'invoice_number', 'plan_name', 'amount', 'status', 'payment_method',
        'billing_cycle_start', 'billing_cycle_end', 'due_date',
        'paid_at', 'mp_payload', 'notes',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'billing_cycle_start'  => 'date',
        'billing_cycle_end'    => 'date',
        'due_date'             => 'date',
        'paid_at'              => 'datetime',
        'mp_payload'           => 'array',
        'deleted_at'           => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function markAsPaid(string $mpPaymentId, ?string $paymentMethod = null): bool
    {
        return $this->update([
            'status'         => 'paid',
            'paid_at'        => now(),
            'mp_payment_id'  => $mpPaymentId,
            'payment_method' => $paymentMethod,
        ]);
    }

    public function markAsFailed(?array $mpPayload = null): bool
    {
        return $this->update([
            'status'     => 'failed',
            'mp_payload' => $mpPayload,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public static function generateNumber(): string
    {
        $last = static::withTrashed()->latest('id')->value('invoice_number');
        $n    = $last ? ((int) ltrim(str_replace('SUB-', '', $last), '0')) + 1 : 1;
        return 'SUB-' . str_pad($n, 7, '0', STR_PAD_LEFT);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateNumber();
            }
        });
    }
}
