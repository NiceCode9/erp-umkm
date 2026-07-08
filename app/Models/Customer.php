<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'address',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
