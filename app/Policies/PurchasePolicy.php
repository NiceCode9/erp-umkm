<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Otorisasi untuk modul pembelian & utang supplier.
 *
 * Permission yang dipakai (lihat PERMISSIONS.md):
 * - manage-purchases        : input transaksi pembelian
 * - view-purchases          : lihat riwayat pembelian
 * - manage-purchase-payments: bayar cicilan / pelunasan utang
 * - manage-purchase-returns : retur pembelian ke supplier
 * - manage-supplier-debts   : halaman utang supplier
 *
 * Setiap metode WAJIB mengecek business_id secara eksplisit. Model Purchase
 * memakai global scope BelongsToBusiness sehingga route model binding sudah
 * otomatis 404 untuk tenant lain — pengecekan di sini adalah lapisan kedua
 * agar aman bila model dipakai lewat jalur unscoped.
 */
class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-purchases') || $user->can('manage-purchases');
    }

    public function view(User $user, Purchase $purchase): Response
    {
        return $this->sameBusiness($user, $purchase)
            ? Response::allow()
            : Response::deny('Pembelian ini bukan milik business Anda.');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-purchases');
    }

    public function pay(User $user, Purchase $purchase): Response
    {
        if (! $this->sameBusiness($user, $purchase)) {
            return Response::deny('Pembelian ini bukan milik business Anda.');
        }

        return $user->can('manage-purchase-payments')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin mencatat pembayaran utang.');
    }

    public function returnPurchase(User $user, Purchase $purchase): Response
    {
        if (! $this->sameBusiness($user, $purchase)) {
            return Response::deny('Pembelian ini bukan milik business Anda.');
        }

        return $user->can('manage-purchase-returns')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin membuat retur pembelian.');
    }

    private function sameBusiness(User $user, Purchase $purchase): bool
    {
        return $user->business_id !== null && $user->business_id === $purchase->business_id;
    }
}