<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'product_id',
        'branch_id',
        'production_order_id',
        'batch_no',
        'quantity_remaining',
        'production_cost',
        'production_code',
        'expired_date',
        'produced_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $batch) {
            if (empty($batch->business_id) && ! empty($batch->product_id)) {
                $batch->business_id = \App\Models\Product::withoutGlobalScopes()
                    ->whereKey($batch->product_id)
                    ->value('business_id');
            }
        });
    }

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

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}
