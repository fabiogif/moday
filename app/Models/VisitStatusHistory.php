<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitStatusHistory extends Model
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $fillable = [
        'tenant_id', 'visit_id', 'from_status', 'to_status', 'changed_by_user_id', 'reason', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
