<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\RawMaterialPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * Sengaja ditulis eksplisit (meski Laravel 11+ sudah bisa menebak lewat
     * konvensi App\Policies\{Model}Policy) supaya pemetaan intent reviewer
     * terbaca langsung dan tidak bergantung pada perilaku tebakan framework.
     *
     * PENTING: policy di bawah WAJIB melakukan pengecekan kepemilikan tenant
     * (business_id) secara manual, karena tabel `users` sengaja TIDAK memakai
     * global scope generik — lihat AGENTS.md bagian 2.1 (menghindari infinite
     * recursion saat resolve Auth::user()).
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Branch::class => BranchPolicy::class,
        User::class => UserPolicy::class,
        RawMaterial::class => RawMaterialPolicy::class,
        Product::class => ProductPolicy::class,
        Purchase::class => PurchasePolicy::class,
        StockMovement::class => StockMovementPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
