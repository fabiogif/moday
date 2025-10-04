<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPlan extends Model
{
    use HasFactory;

    protected $table = 'detail_plans';

    protected $fillable = ['name', 'description', 'plan_id', 'created_at', 'updated_at'];

    public function plan()
    {
        $this->belongsTo(Plan::class);
    }

}
