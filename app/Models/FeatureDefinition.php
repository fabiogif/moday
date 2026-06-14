<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureDefinition extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'icon',
        'plan_tier',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function planFeatures()
    {
        return $this->hasMany(PlanFeature::class, 'feature_key', 'key');
    }
}
