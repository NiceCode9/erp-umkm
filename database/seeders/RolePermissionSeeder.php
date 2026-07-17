<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Business
            'manage-businesses',
            
            // Branches
            'manage-branches',
            'view-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',
            
            // Users
            'manage-users',
            'edit-own-profile',
            'create-kasir',
            'edit-kasir',
            'reset-kasir-password',
            'manage-kasir',
            
            // Raw Materials
            'manage-raw-materials',
            'view-raw-material-stock',
            
            // Stock Opname
            'manage-stock-opname',
            
            // Purchases
            'manage-purchases',
            'view-purchases',
            'manage-purchase-payments',
            'manage-purchase-returns',
            
            // Recipes
            'manage-recipes',
            
            // Production
            'manage-production',
            'view-production',
            
            // Products
            'manage-products',
            'manage-product-prices',
            
            // Sales
            'create-sales',
            'view-own-sales',
            'view-all-sales',
            'manage-sale-payments',
            'manage-sale-returns',
            
            // Cashier Shifts
            'manage-cashier-shifts',
            'view-all-shifts',
            
            // Shipments
            'manage-shipments',
            
            // Debts/Receivables
            'manage-supplier-debts',
            'manage-customer-receivables',
            
            // Reports
            'view-reports',
            'export-reports',
            
            // Branch Settings
            'manage-branch-settings',
            
            // Dashboards
            'view-superadmin-dashboard',
            'view-owner-dashboard',
            'view-kasir-dashboard',
            
            // New granular permissions
            'create-branches',
            'edit-branches',
            'delete-branches',
            'view-branches',
            'manage-branches',
            'create-kasir',
            'edit-kasir',
            'reset-kasir-password',
            'manage-kasir',
            'manage-branches',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        
        $superadmin = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $owner = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $kasir = Role::firstOrCreate(['name' => 'Kasir', 'guard_name' => 'web']);
        
        $superadmin->syncPermissions([
            'manage-businesses',
            'view-superadmin-dashboard',
        ]);
        
        $owner->syncPermissions([
            'manage-branches',
            'view-branches',
            'edit-branches',
            'manage-users',
            'edit-own-profile',
            'manage-raw-materials',
            'view-raw-material-stock',
            'manage-stock-opname',
            'manage-purchases',
            'view-purchases',
            'manage-purchase-payments',
            'manage-purchase-returns',
            'manage-recipes',
            'manage-production',
            'view-production',
            'manage-products',
            'manage-product-prices',
            'view-all-sales',
            'manage-sale-payments',
            'manage-sale-returns',
            'view-all-shifts',
            'manage-shipments',
            'manage-supplier-debts',
            'manage-customer-receivables',
            'view-reports',
            'export-reports',
            'manage-branch-settings',
            'view-owner-dashboard',
            // New granular permissions
            'edit-branches',
            'view-branches',
            'edit-kasir',
            'reset-kasir-password',
            'manage-kasir',
        ]);
        
        $kasir->syncPermissions([
            'edit-own-profile',
            'view-branches',
            'create-sales',
            'view-own-sales',
            'manage-sale-payments',
            'manage-cashier-shifts',
            'view-kasir-dashboard',
        ]);
    }
}