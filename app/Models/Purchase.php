<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float $paid_amount
 * @property float $returned_amount
 * @property float $outstanding_amount
 * @property-read float $paidAmount
 * @property-read float $returnedAmount
 * @property-read float $outstandingAmount
 */
class Purchase extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'branch_id',
        'supplier_id',
        'user_id',
        'invoice_no',
        'purchase_date',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_percentage_applied',
        'tax_amount',
        'total_amount',
        'payment_status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage_applied' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * Total yang sudah dibayar (cicilan + pelunasan).
     *
     * Mengutamakan nilai hasil withSum('payments') supaya tidak query per baris.
     * Lihat PurchaseService untuk eager loading yang dipakai list & laporan.
     */
    protected function paidAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveSum('paid_amount', fn () => (float) $this->payments()->sum('amount')),
        );
    }

    /**
     * Total retur ke supplier. Mengurangi utang supplier sesuai
     * BUSINESS-RULES.md bagian 5.
     */
    protected function returnedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveSum('returned_amount', fn () => (float) $this->returns()->sum('total_amount')),
        );
    }

    /**
     * Utang outstanding — SATU-SATUNYA formula resmi (business rules bagian 5).
     *
     *   outstanding = total_amount - SUM(payments) - SUM(returns.total_amount)
     *
     * Boleh NEGATIF: retur yang melebihi sisa utang berarti supplier owes kita
     * (status 'credit'). JANGAN di-clamp ke 0 di sini — nilai nolnya hilang dari
     * semua laporan. Nilai negatif memang ditampilkan sebagai kredit.
     */
    protected function outstandingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveSum(
                'outstanding_amount',
                fn () => (float) $this->total_amount - $this->paidAmount - $this->returnedAmount,
            ),
        );
    }

    /**
     * Backward-compatible alias. Dipakai jadi Max(0, outstanding) supaya
     * "sisa yang harus dibayar" tidak pernah tampil negatif di form pembayaran.
     */
    public function remainingAmount(): float
    {
        return max(0.0, (float) $this->outstanding_amount);
    }

    public function hasCredit(): bool
    {
        return (float) $this->outstanding_amount < 0;
    }

    public function isFullyPaid(): bool
    {
        return ! $this->hasCredit() && (float) $this->outstanding_amount === 0.0;
    }

    /**
     * Pakai nilai yang sudah di-eager-load (withSum) kalau ada, kalau tidak
     * hitung langsung.
     *
     * WAJIB membaca $this->attributes secara langsung, BUKAN $this->{$key}:
     * memakai notasi objek akan memanggil accessor yang sama lagi (accessor
     * `outstandingAmount()` dilayani oleh atribut `outstanding_amount`) dan
     * menyebabkan rekursi tak terbatas.
     */
    private function resolveSum(string $key, callable $fallback): float
    {
        if (array_key_exists($key, $this->attributes) && $this->attributes[$key] !== null) {
            return (float) $this->attributes[$key];
        }

        return (float) $fallback();
    }
}