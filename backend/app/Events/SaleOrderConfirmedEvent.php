<?php

namespace App\Events;

use App\Models\SaleOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleOrderConfirmedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly SaleOrder $order) {}
}
