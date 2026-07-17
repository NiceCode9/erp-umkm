<?php

namespace App\Policies;

use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RawMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Kasir', 'Superadmin']);
    }

    public function view(User $user, RawMaterial $rawMaterial): bool
    {
        return $user->hasAnyRole(['Owner', 'Superadmin']) && $rawMaterial->business_id === $user->business_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Owner') || $user->hasRole('Superadmin');
    }

    public function update(User $user, RawMaterial $rawMaterial): bool
    {
        if (!$user->hasAnyRole(['Owner', 'Superadmin'])) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $rawMaterial->business_id === $user->business_id;
    }

    public function delete(User $user, RawMaterial $rawMaterial): bool
    {
        return $user->hasRole('Superadmin');
    }
}