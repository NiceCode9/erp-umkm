<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-branches');
    }

    public function view(User $user, User $kasir): bool
    {
        return $user->business_id === $kasir->business_id && $user->can('view-branches');
    }

    public function create(User $user): bool
    {
        return $user->can('create-kasir');
    }

    public function update(User $user, User $kasir): bool
    {
        if ($user->business_id !== $kasir->business_id) {
            return false;
        }

        // Allow update if user can manage-kasir (Owner & Superadmin)
        // But restrict: Owner cannot change role of other users
        if ($user->hasRole('Owner') && $kasir->hasRole('Owner')) {
            return false;
        }

        return $user->can('manage-kasir') || $user->can('edit-kasir');
    }

    public function resetPassword(User $user, User $kasir): bool
    {
        if ($user->business_id !== $kasir->business_id) {
            return false;
        }

        return $user->can('reset-kasir-password');
    }

    public function delete(User $user, User $kasir): bool
    {
        return $user->business_id === $kasir->business_id && $user->can('manage-kasir');
    }

    public function restore(User $user, User $kasir): bool
    {
        return $user->business_id === $kasir->business_id && $user->can('manage-kasir');
    }

    public function forceDelete(User $user, User $kasir): bool
    {
        return $user->business_id === $kasir->business_id && $user->can('manage-kasir');
    }
}