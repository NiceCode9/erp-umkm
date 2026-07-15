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

    protected $appends = ['batch_name', 'batch_expired'];

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

    public function getBatchNameAttribute(): string
    {
        $item = $this->distributionItem;
        if (!$item) return '-';

        if ($item->item_type === 'raw_material') {
            return $this->rawMaterialBatch?->batch_no ?? '-';
        }
        if ($item->item_type === 'product') {
            return $this->productBatch?->batch_no ?? '-';
        }
        return '-';
    }

    public function getBatchExpiredAttribute(): ?string
    {
        $item = $this->distributionItem;
        if (!$item) return null;

        if ($item->item_type === 'raw_material') {
            return $this->rawMaterialBatch?->expired_date?->format('d/m/Y');
        }
        if ($item->item_type === 'product') {
            return $this->productBatch?->expired_date?->format('d/m/Y');
        }
        return null;
    }
}
