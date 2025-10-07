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
        'email', 
        'password', 
        'uuid', 
        'url',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'neighborhood',
        'number',
        'complement',
        'is_active',
        'tenant_id'
    ];

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
    
    public function OrderEvaluation()
    {
        return $this->hasMany(OrderEvaluation::class);
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
