<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'production_code',
        'business_id',
        'branch_id',
        'product_id',
        'recipe_id',
        'user_id',
        'quantity_target',
        'batch_multiplier',
        'expired_date',
        'status',
        'produced_at',
    ];

    protected $casts = [
        'quantity_target' => 'decimal:2',
        'batch_multiplier' => 'decimal:2',
        'expired_date' => 'date',
        'produced_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class);
    }
}
