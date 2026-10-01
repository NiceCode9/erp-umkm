<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $business_id
 * @property int $raw_material_id
 * @property int $branch_id
 * @property string $batch_no
 * @property float $quantity_remaining
 * @property float $purchase_price
 * @property \Illuminate\Support\Carbon|null $expired_date
 * @property \Illuminate\Support\Carbon $received_at
 */
class RawMaterialBatch extends Model
{
    use BelongsToBusiness;

    protected $table = 'raw_material_batches';

    protected $fillable = [
        'business_id',
        'raw_material_id',
        'branch_id',
        'batch_no',
        'quantity_remaining',
        'purchase_price',
        'expired_date',
        'received_at',
    ];

    protected $casts = [
        'quantity_remaining' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'expired_date' => 'date',
        'received_at' => 'date',
    ];

    protected static function booted(): void
    {
        /**
         * business_id bersifat NOT NULL, tapi trait BelongsToBusiness hanya bisa
         * menebaknya dari auth() — yang tidak ada di seeder, artisan command, atau
         * queue job. Karena batch SELALU punya raw_material yang punya business_id,
         * turunkan dari situ bila belum diisi. Ini membuat kolom aman diisi dari
         * jalur mana pun, bukan hanya request HTTP.
         */
        static::creating(function (self $batch) {
            if (empty($batch->business_id) && ! empty($batch->raw_material_id)) {
                $batch->business_id = RawMaterial::withoutGlobalScopes()
                    ->whereKey($batch->raw_material_id)
                    ->value('business_id');
            }
        });
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'raw_material_batch_id');
    }
}