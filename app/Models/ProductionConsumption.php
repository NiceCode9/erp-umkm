<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionConsumption extends Model
{
    protected $fillable = [
        'production_order_id',
        'raw_material_batch_id',
        'quantity_deducted',
    ];

    protected $casts = [
        'quantity_deducted' => 'decimal:2',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function rawMaterialBatch(): BelongsTo
    {
        return $this->belongsTo(RawMaterialBatch::class);
    }
}
