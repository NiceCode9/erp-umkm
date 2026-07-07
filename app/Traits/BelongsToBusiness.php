<?php

namespace App\Traits;

use App\Models\Business;
use App\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->business_id && !$model->business_id) {
                $model->business_id = auth()->user()->business_id;
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
