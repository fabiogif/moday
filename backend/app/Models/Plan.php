<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'url', 'description', 'price'];

    public function details()
    {
        return $this->hasMany(DetailPlan::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
