<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class TenantBankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'account_type',
        'bank_code',
        'bank_name',
        'agency',
        'agency_digit',
        'account_number',
        'account_digit',
        'account_holder_name',
        'account_holder_document',
        'account_holder_type',
        'pix_key_type',
        'pix_key',
        'is_primary',
        'is_active',
        'is_verified',
        'verified_at',
        'verification_method',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $appends = [
        'masked_account_number',
        'masked_document',
    ];

    /**
     * Auto-generate UUID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function logs()
    {
        return $this->hasMany(BankAccountLog::class, 'bank_account_id');
    }

    /**
     * Accessors (Decrypt)
     */
    public function getAccountNumberAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function getAccountDigitAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function getAccountHolderNameAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function getAccountHolderDocumentAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function getPixKeyAttribute($value)
    {
        if (!$value) return null;
        
        // Só descriptografar chaves sensíveis (CPF/CNPJ/Phone)
        if (in_array($this->attributes['pix_key_type'] ?? null, ['cpf', 'cnpj', 'phone'])) {
            return Crypt::decryptString($value);
        }
        
        return $value;
    }

    /**
     * Mutators (Encrypt)
     */
    public function setAccountNumberAttribute($value)
    {
        $this->attributes['account_number'] = Crypt::encryptString($value);
    }

    public function setAccountDigitAttribute($value)
    {
        $this->attributes['account_digit'] = Crypt::encryptString($value);
    }

    public function setAccountHolderNameAttribute($value)
    {
        $this->attributes['account_holder_name'] = Crypt::encryptString($value);
    }

    public function setAccountHolderDocumentAttribute($value)
    {
        // Remove formatação
        $cleaned = preg_replace('/[^0-9]/', '', $value);
        $this->attributes['account_holder_document'] = Crypt::encryptString($cleaned);
    }

    public function setPixKeyAttribute($value)
    {
        if (!$value) {
            $this->attributes['pix_key'] = null;
            return;
        }
        
        // Só criptografar chaves sensíveis
        if (in_array($this->attributes['pix_key_type'] ?? null, ['cpf', 'cnpj', 'phone'])) {
            $this->attributes['pix_key'] = Crypt::encryptString($value);
        } else {
            $this->attributes['pix_key'] = $value;
        }
    }

    /**
     * Computed Attributes
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        if (!isset($this->attributes['account_number'])) {
            return '****';
        }
        
        $number = $this->account_number;
        $digit = $this->account_digit;
        
        if (strlen($number) <= 4) {
            return str_repeat('*', strlen($number)) . '-' . $digit;
        }
        
        return substr($number, 0, 2) . str_repeat('*', strlen($number) - 4) . substr($number, -2) . '-' . $digit;
    }

    public function getMaskedDocumentAttribute(): string
    {
        if (!isset($this->attributes['account_holder_document'])) {
            return '***.***.***-**';
        }
        
        $doc = $this->account_holder_document;
        
        if (strlen($doc) === 11) { // CPF
            return substr($doc, 0, 3) . '.***.***-' . substr($doc, -2);
        } else { // CNPJ
            return substr($doc, 0, 2) . '.***.***/****.****-' . substr($doc, -2);
        }
    }

    /**
     * Methods
     */
    public function setPrimary(): bool
    {
        // Remove primary de todas as outras contas do tenant
        self::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
        
        // Define esta como primary
        return $this->update(['is_primary' => true]);
    }

    public function verify(string $method = 'manual'): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verification_method' => $method,
        ]);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('account_type', $type);
    }
}

