<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDistributionItemBatch extends Model
{
    protected $fillable = [
        'stock_distribution_item_id',
        'product_batch_id',
        'raw_material_batch_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function distributionItem(): BelongsTo
    {
        return $this->belongsTo(StockDistributionItem::class, 'stock_distribution_item_id');
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function rawMaterialBatch(): BelongsTo
    {
        return $this->belongsTo(RawMaterialBatch::class, 'raw_material_batch_id');
    }
}
