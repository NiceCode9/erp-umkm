<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission yang dicabut: tidak lagi direferensikan kode mana pun.
     * Dihapus baris di tabel `permissions` supaya tidak jadi granted-yang-tak-terpakai.
     */
    private const RETIRED_PERMISSIONS = [
        'manage-branches',
        'manage-users',
    ];

    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Daftar kanonik. Section = modul, dengan route yang dilayaninya.
        // Peringatan: JANGAN menambah nama di sini tanpa impact review —
        // permission yang tidak dipakai route/controller mana pun adalah dead grant.
        $permissions = [
            // Superadmin
            'manage-businesses',
            'view-superadmin-dashboard',

            // Cabang  -> routes app.branches.* (area Owner)
            'view-branches',     // GET  app/branches
            'create-branches',   // Superadmin-saja; route area Owner sengaja 403
            'edit-branches',     // PUT  app/branches/{branch}
            'delete-branches',   // Superadmin-saja; route area Owner sengaja 403

            // Akun
            'edit-own-profile',      // profile/edit, profile/update
            'create-kasir',          // Superadmin-saja; route area Owner sengaja 403
            'edit-kasir',            // app/kasir/{kasir} (edit + update)
            'reset-kasir-password',  // app/kasir/{kasir}/reset-password
            'manage-kasir',          // hapus/nonaktifkan kasir (dipakai UserPolicy)

            // Bahan baku
            'manage-raw-materials',
            'view-raw-material-stock',

            // Stok opname
            'manage-stock-opname',

            // Pembelian
            'manage-purchases',
            'view-purchases',
            'manage-purchase-payments',
            'manage-purchase-returns',

            // Resep & produksi
            'manage-recipes',
            'manage-production',
            'view-production',

            // Produk jadi
            'manage-products',
            'manage-product-prices',

            // Penjualan
            'create-sales',
            'view-own-sales',
            'view-all-sales',
            'manage-sale-payments',
            'manage-sale-returns',

            // Shift kasir
            'manage-cashier-shifts',
            'view-all-shifts',

            // Pengiriman
            'manage-shipments',

            // Utang & piutang
            'manage-supplier-debts',
            'manage-customer-receivables',

            // Laporan
            'view-reports',
            'export-reports',

            // Pengaturan per cabang
            'manage-branch-settings',

            // Dashboard
            'view-owner-dashboard',
            'view-kasir-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superadmin = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $owner = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $kasir = Role::firstOrCreate(['name' => 'Kasir', 'guard_name' => 'web']);

        // Superadmin = superuser, dapat seluruh permission. Area /superadmin/* hanya
        // dijaga middleware role:Superadmin, jadi permission di sini adalah lapisan
        // kedua — disimpan lengkap supaya penambahan fitur baru tidak ikut 403.
        $superadmin->syncPermissions($permissions);

        // Owner: operasional harian. TIDAK diberi create-branches / create-kasir /
        // delete-branches karena provisioning struktur tenant hanya lewat Superadmin
        // (lihat PERMISSIONS.md, keputusan final).
        $owner->syncPermissions([
            'edit-own-profile',
            'view-owner-dashboard',

            // Cabang: lihat & ubah, tidak boleh tambah/hapus
            'view-branches',
            'edit-branches',

            // Kasir: ubah & reset password, tidak boleh buat baru
            'edit-kasir',
            'manage-kasir',
            'reset-kasir-password',

            // Bahan baku & stok
            'manage-raw-materials',
            'view-raw-material-stock',
            'manage-stock-opname',

            // Pembelian
            'manage-purchases',
            'view-purchases',
            'manage-purchase-payments',
            'manage-purchase-returns',

            // Produksi
            'manage-recipes',
            'manage-production',
            'view-production',

            // Produk
            'manage-products',
            'manage-product-prices',

            // Penjualan (melihat semua kasir/cabang)
            'view-all-sales',
            'manage-sale-payments',
            'manage-sale-returns',
            'view-all-shifts',

            // Pengiriman, utang piutang
            'manage-shipments',
            'manage-supplier-debts',
            'manage-customer-receivables',

            // Laporan & pengaturan
            'view-reports',
            'export-reports',
            'manage-branch-settings',
        ]);

        // Kasir: operasional kasir di cabangnya sendiri.
        // CATATAN: `view-branches` sengaja tidak diberikan — route app.branches.*
        // berada di dalam group role:Owner sehingga tidak pernah bisa diakses Kasir.
        // Grant ini ditahan sampai fitur "lihat cabang sendiri" benar-benar ada.
        $kasir->syncPermissions([
            'edit-own-profile',
            'view-kasir-dashboard',
            'create-sales',
            'view-own-sales',
            'manage-sale-payments',
            'manage-cashier-shifts',
        ]);

        // Bersihkan permission yang sudah dicabut. syncPermissions() di atas sudah
        // melepas pivot lama; langkah ini menghapus baris yang sudah tidak relevan.
        Permission::whereIn('name', self::RETIRED_PERMISSIONS)
            ->where('guard_name', 'web')
            ->delete();

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
