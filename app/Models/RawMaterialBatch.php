<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialBatch extends Model
{
    protected $table = 'raw_material_batches';

    protected $fillable = [
        'raw_material_id',
        'branch_id',
        'batch_no',
        'quantity_remaining',
        'purchase_price',
        'expired_date',
        'received_at',
    ];

    protected $casts = [
        'quantity_remaining' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'expired_date' => 'date',
        'received_at' => 'date',
    ];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
