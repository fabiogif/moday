<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'slug',
        'cnpj', 
        'email', 
        'phone',
        'address',
        'city',
        'state',
        'zipcode',
        'country',
        'url', 
        'logo', 
        'active', 
        'is_active',
        'settings',
        'subscription', 
        'expire_at', 
        'plan_id', 
        'uuid',
        'subscription_id', 
        'subscription_plan', 
        'subscription_active', 
        'subscription_suspended'
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscription_active' => 'boolean',
            'subscription_suspended' => 'boolean',
            'settings' => 'array',
            'subscription' => 'date',
            'expire_at' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Scope para tenants ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

//    public function categories()
//    {
//        return $this->hasMany(Category::class);
//    }

}
