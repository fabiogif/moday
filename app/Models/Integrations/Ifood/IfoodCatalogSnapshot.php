<?php

namespace App\Models\Integrations\Ifood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IfoodCatalogSnapshot extends Model
{
    use HasFactory;

    protected $table = 'ifood_catalog_snapshots';

    protected $fillable = [
        'tenant_id',
        'merchant_id',
        'catalog_id',
        'group_id',
        'snapshot_type',
        'payload',
        'captured_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'captured_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\Integrations\Ifood\IfoodCatalogSnapshotFactory::new();
    }
}

