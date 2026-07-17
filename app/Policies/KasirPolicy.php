<?php

namespace App\Policies;

use App\Models\Kasir;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KasirPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Kasir $kasir): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']) && $kasir->business_id === $user->business_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Kasir $kasir): bool
    {
        if (!$user->hasAnyRole(['Owner', 'Superadmin'])) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $kasir->business_id === $user->business_id;
    }

    /**
     * Determine whether the user can reset the password of the kasir.
     */
    public function resetPassword(User $user, Kasir $kasir): bool
    {
        if (!$user->hasAnyRole(['Owner', 'Superadmin'])) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $kasir->business_id === $user->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Kasir $kasir): bool
    {
        // Only Superadmin can delete kasir accounts
        return $user->hasRole('Superadmin');
    }
}