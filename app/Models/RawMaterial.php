<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterial extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'base_unit',
        'minimum_stock',
    ];

    protected $casts = [
        'minimum_stock' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
