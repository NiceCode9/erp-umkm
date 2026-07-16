<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpnameSession extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'branch_id', 'item_type',
        'title', 'status', 'opname_date',
        'user_id', 'confirmed_at', 'confirmed_by',
    ];

    protected $casts = [
        'opname_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(StockOpname::class, 'session_id');
    }
}
