<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToBusiness;

    protected $appends = ['reference_label'];

    public function getReferenceLabelAttribute(): string
    {
        $label = str_replace('_', ' ', $this->reference_type);
        $label = ucwords($label);

        if ($this->reference_type === 'production') {
            $code = optional($this->batch?->production_code)
                ?? optional(\App\Models\ProductionOrder::find($this->reference_id))?->production_code;
            if ($code) {
                return "Produksi ({$code})";
            }
            return "Produksi #{$this->reference_id}";
        }

        return "{$label} #{$this->reference_id}";
    }

    protected $fillable = [
        'business_id',
        'branch_id',
        'item_type',
        'item_id',
        'batch_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(RawMaterialBatch::class, 'batch_id');
    }
}
