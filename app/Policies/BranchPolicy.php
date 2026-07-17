<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BranchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Kasir', 'Superadmin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']) && $branch->business_id === $user->business_id;
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
    public function update(User $user, Branch $branch): bool
    {
        if (!$user->hasAnyRole(['Owner', 'Superadmin'])) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $branch->business_id === $user->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Deleting branches is restricted - only superadmin can do it
        // But we don't have a delete route for branches currently
        return $user->hasRole('Superadmin');
    }
}