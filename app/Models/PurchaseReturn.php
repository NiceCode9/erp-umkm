<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'purchase_id',
        'business_id',
        'branch_id',
        'user_id',
        'return_date',
        'reason',
        'total_amount',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}