<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'raw_material_id',
        'quantity',
        'unit_price',
        'subtotal',
        'batch_no',
        'expired_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'expired_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_item_id');
    }

    /**
     * Berapa quantity item ini yang sudah diretur ke supplier.
     * Dipakai untuk menolak retur melebihi jumlah yang dibeli.
     */
    public function returnedQuantity(): float
    {
        return (float) $this->returnItems()->sum('quantity');
    }

    public function returnableQuantity(): float
    {
        return max(0.0, (float) $this->quantity - $this->returnedQuantity());
    }
}
