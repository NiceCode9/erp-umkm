<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'base_unit',
        'minimum_stock',
        'halal_cert_expired_date',
    ];

    protected $casts = [
        'minimum_stock' => 'decimal:2',
        'halal_cert_expired_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(RawMaterialBatch::class);
    }

    /**
     * Bahan baku yang sudah melewati masa kedaluwarsa sertifikat halal.
     * Bahan seperti ini DIBLOKIR total untuk produksi — lihat StockService.
     */
    public function scopeHalalExpired(Builder $query): Builder
    {
        return $query->whereNotNull('halal_cert_expired_date')
            ->where('halal_cert_expired_date', '<', now()->startOfDay());
    }

    /**
     * Bahan baku yang akan melewati masa kedaluwarsa sertifikat halal
     * dalam $days hari ke depan (termasuk yang sudah lewat).
     */
    public function scopeHalalExpiringWithin(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('halal_cert_expired_date')
            ->whereBetween('halal_cert_expired_date', [
                now()->startOfDay(),
                now()->startOfDay()->copy()->addDays($days),
            ]);
    }

    public function isHalalExpired(): bool
    {
        return $this->halal_cert_expired_date !== null
            && $this->halal_cert_expired_date->lt(now()->startOfDay());
    }

    public function isHalalExpiringWithin(int $days = 30): bool
    {
        return $this->halal_cert_expired_date !== null
            && $this->halal_cert_expired_date->between(
                now()->startOfDay(),
                now()->startOfDay()->copy()->addDays($days)
            );
    }
}
