<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Client extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $entity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cpf',
        'cnpj',
        'company_name',
        'trade_name',
        'state_registration',
        'email',
        'password',
        'uuid',
        'client_request_id',
        'url',
        'phone',
        'contact_name',
        'contact_phone',
        'contact_email',
        'whatsapp',
        'address',
        'city',
        'state',
        'zip_code',
        'neighborhood',
        'number',
        'complement',
        'client_type',
        'credit_limit',
        'payment_term_days',
        'price_table_id',
        'is_blocked',
        'blocked_reason',
        'notes',
        'is_active',
        'tenant_id',
        'latitude',
        'longitude',
        'abc_classification',
        'is_vip',
        'business_hours_start',
        'business_hours_end',
        'last_visit_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_blocked' => 'boolean',
            'credit_limit' => 'decimal:2',
            'payment_term_days' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_vip' => 'boolean',
            'last_visit_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'tenant_id' => $this->tenant_id,
            'is_active' => $this->is_active,
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class);
    }

    public function priceTable()
    {
        return $this->belongsTo(PriceTable::class);
    }
    
    public function OrderEvaluation()
    {
        return $this->hasMany(OrderEvaluation::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get the full address formatted
     */
    public function getFullAddressAttribute()
    {
        $addressParts = array_filter([
            $this->address,
            $this->number,
            $this->complement,
            $this->neighborhood,
            $this->city,
            $this->state,
            $this->zip_code
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Check if client has complete address
     */
    public function hasCompleteAddress()
    {
        return !empty($this->address) && !empty($this->city) && !empty($this->state);
    }
}
