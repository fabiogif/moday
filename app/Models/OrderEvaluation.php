<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderEvaluation extends Model
{
    use HasFactory;
    protected $table = 'order_evaluations';

    protected $fillable = ['stars', 'comment', 'order_id', 'client_id', 'tenant_id'];

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }


}
