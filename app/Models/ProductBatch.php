<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'batch_no',
        'quantity_remaining',
        'production_cost',
        'production_code',
        'expired_date',
        'produced_at',
    ];

    protected $casts = [
        'quantity_remaining' => 'decimal:2',
        'production_cost' => 'decimal:2',
        'expired_date' => 'date',
        'produced_at' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
