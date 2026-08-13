<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferRuleProduct extends Model
{
    protected $fillable = [
        'offer_rule_id',
        'product_id',
        'role',
        'min_quantity',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
    ];

    public function offerRule()
    {
        return $this->belongsTo(OfferRule::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
