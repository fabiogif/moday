<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalWebhookEvent extends Model
{
    protected $fillable = [
        'tenant_id', 'sale_order_id', 'provider', 'event_type', 'status', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }
}
