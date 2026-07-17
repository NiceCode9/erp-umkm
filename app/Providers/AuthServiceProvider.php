<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Kasir;
use App\Models\RawMaterial;
use App\Models\Product;
use App\Models\StockMovement;
use App\Policies\BranchPolicy;
use App\Policies\KasirPolicy;
use App\Policies\RawMaterialPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StockMovementPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Branch::class => BranchPolicy::class,
        Kasir::class => KasirPolicy::class,
        RawMaterial::class => RawMaterialPolicy::class,
        Product::class => ProductPolicy::class,
        StockMovement::class => StockMovementPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}