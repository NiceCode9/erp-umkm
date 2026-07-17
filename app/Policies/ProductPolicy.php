<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']) && $product->business_id === $user->business_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Owner') || $user->hasRole('Superadmin');
    }

    public function update(User $user, Product $product): bool
    {
        if (!$user->hasAnyRole(['Owner', 'Superadmin'])) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $product->business_id === $user->business_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('Superadmin');
    }
}