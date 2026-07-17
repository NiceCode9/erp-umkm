<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']);
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']) && $stockMovement->business_id === $user->business_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function update(User $user, StockMovement $stockMovement): bool
    {
        return false; // Stock movements should not be updated after creation
    }

    public function delete(User $user, StockMovement $stockMovement): bool
    {
        return $user->hasRole('Superadmin');
    }
}