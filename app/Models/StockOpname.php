<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpname extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'branch_id',
        'item_type', 'item_id', 'batch_id',
        'system_quantity', 'actual_quantity', 'difference',
        'reason', 'user_id',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    protected $appends = ['item_name', 'batch_label'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getItemNameAttribute(): string
    {
        if ($this->item_type === 'raw_material') {
            return RawMaterial::find($this->item_id)?->name ?? "Bahan Baku #{$this->item_id}";
        }
        if ($this->item_type === 'product') {
            return Product::find($this->item_id)?->name ?? "Produk #{$this->item_id}";
        }
        return "Item #{$this->item_id}";
    }

    public function getBatchLabelAttribute(): string
    {
        if ($this->item_type === 'raw_material') {
            return RawMaterialBatch::find($this->batch_id)?->batch_no ?? '-';
        }
        if ($this->item_type === 'product') {
            return ProductBatch::find($this->batch_id)?->batch_no ?? '-';
        }
        return '-';
    }
}
