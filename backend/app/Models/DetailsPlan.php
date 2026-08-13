<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailsPlan extends Model
{
    use HasFactory;

    protected $table = 'details_plan';
    
    protected $fillable = ['plan_id', 'name', 'description'];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
