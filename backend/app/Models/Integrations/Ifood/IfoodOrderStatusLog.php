<?php

namespace App\Models\Integrations\Ifood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IfoodOrderStatusLog extends Model
{
    use HasFactory;

    protected $table = 'ifood_order_status_logs';

    protected $fillable = [
        'ifood_order_id',
        'status',
        'sent_to_ifood_at',
        'response_code',
        'response_body',
        'error_message',
    ];

    protected $casts = [
        'sent_to_ifood_at' => 'datetime',
        'response_body' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(IfoodOrder::class, 'ifood_order_id');
    }
}

