<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy untuk mengelola akun Kasir oleh Owner.
 *
 * TIDAK memakai global scope generik (lihat AGENTS.md bagian 2.1), jadi SETIAP
 * metode di sini wajib memeriksa `business_id` secara eksplisit. Route model
 * binding `{kasir}` tidak otomatis ter-scope ke tenant Owner.
 */
class UserPolicy
{
    /**
     * Daftar permission yang mengizinkan mengelola akun Kasir.
     */
    private const KASIR_PERMISSIONS = ['edit-kasir', 'manage-kasir'];

    public function viewAny(User $user): bool
    {
        return $this->managesKasir($user);
    }

    public function view(User $user, User $kasir): Response
    {
        return $this->sameBusiness($user, $kasir) && $this->isKasir($kasir)
            ? Response::allow()
            : Response::deny('Kasir ini bukan milik business Anda.');
    }

    public function update(User $user, User $kasir): Response
    {
        if (!$this->sameBusiness($user, $kasir)) {
            return Response::deny('Kasir ini bukan milik business Anda.');
        }

        if (!$this->isKasir($kasir)) {
            return Response::deny('Hanya akun dengan role Kasir yang dapat diubah dari halaman ini.');
        }

        return $this->managesKasir($user)
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin mengelola akun Kasir.');
    }

    public function resetPassword(User $user, User $kasir): Response
    {
        if (!$this->sameBusiness($user, $kasir)) {
            return Response::deny('Kasir ini bukan milik business Anda.');
        }

        if (!$this->isKasir($kasir)) {
            return Response::deny('Hanya akun dengan role Kasir yang passwordnya dapat direset.');
        }

        return $user->can('reset-kasir-password')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin reset password Kasir.');
    }

    public function delete(User $user, User $kasir): Response
    {
        if (!$this->sameBusiness($user, $kasir) || !$this->isKasir($kasir)) {
            return Response::deny();
        }

        return $user->can('manage-kasir')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin menghapus akun Kasir.');
    }

    public function restore(User $user, User $kasir): Response
    {
        return $this->delete($user, $kasir);
    }

    public function forceDelete(User $user, User $kasir): Response
    {
        return $this->delete($user, $kasir);
    }

    private function sameBusiness(User $user, User $target): bool
    {
        return $user->business_id !== null && $user->business_id === $target->business_id;
    }

    private function isKasir(User $target): bool
    {
        return $target->hasRole('Kasir');
    }

    private function managesKasir(User $user): bool
    {
        foreach (self::KASIR_PERMISSIONS as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
