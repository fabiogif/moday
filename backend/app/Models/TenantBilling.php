<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantBilling extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenant_billing';

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'billing_date',
        'due_date',
        'amount',
        'status',
        'plan_name',
        'description',
        'paid_at',
        'payment_method',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function($q) {
                $q->where('status', 'pending')
                  ->where('due_date', '<', today());
            });
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('billing_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('billing_date', 'desc');
    }

    // Relations
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Methods
    public function markAsPaid(string $paymentMethod = null): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
        ]);
    }

    public function markAsOverdue(): bool
    {
        if ($this->status === 'pending' && $this->due_date < today()) {
            return $this->update(['status' => 'overdue']);
        }
        return false;
    }

    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || 
               ($this->status === 'pending' && $this->due_date < today());
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function daysUntilDue(): int
    {
        return today()->diffInDays($this->due_date, false);
    }

    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return $this->due_date->diffInDays(today());
    }

    public static function generateInvoiceNumber(): string
    {
        $lastInvoice = self::latest('id')->first();
        $lastNumber = $lastInvoice 
            ? (int) substr($lastInvoice->invoice_number, 4) 
            : 0;
        
        $newNumber = $lastNumber + 1;
        
        return 'INV-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public static function createForTenant(Tenant $tenant, float $amount, string $planName): self
    {
        return self::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => self::generateInvoiceNumber(),
            'billing_date' => today(),
            'due_date' => today()->addDays(7),
            'amount' => $amount,
            'status' => 'pending',
            'plan_name' => $planName,
            'description' => "Assinatura do plano {$planName}",
        ]);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($billing) {
            if (empty($billing->invoice_number)) {
                $billing->invoice_number = self::generateInvoiceNumber();
            }
        });
    }
}

