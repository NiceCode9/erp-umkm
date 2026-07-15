<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockDistributionItem extends Model
{
    protected $fillable = [
        'stock_distribution_id',
        'item_type',
        'item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    protected $appends = ['item_name'];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(StockDistribution::class, 'stock_distribution_id');
    }

    public function batchRecords(): HasMany
    {
        return $this->hasMany(StockDistributionItemBatch::class, 'stock_distribution_item_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function getItemNameAttribute(): string
    {
        if ($this->item_type === 'raw_material') {
            return $this->rawMaterial?->name ?? "Bahan Baku #{$this->item_id}";
        }
        if ($this->item_type === 'product') {
            return $this->product?->name ?? "Produk #{$this->item_id}";
        }
        return "Item #{$this->item_id}";
    }
}
