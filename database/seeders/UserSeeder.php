<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperadmin();
        $this->seedOwnerAndKasir();
    }

    private function seedSuperadmin(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@erp.local'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password123'),
                'business_id' => null,
                'branch_id' => null,
                'is_active' => true,
            ]
        )->syncRoles(['Superadmin']);

        echo "✓ Superadmin user created/updated\n";
    }

    private function seedOwnerAndKasir(): void
    {
        $business = Business::firstOrCreate(
            ['name' => 'UMKM Toko Roti Berkah'],
            [
                'owner_name' => 'Budi Santoso',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 123, Jakarta',
                'is_active' => true,
            ]
        );

        $mainBranch = Branch::firstOrCreate(
            [
                'business_id' => $business->id,
                'name' => 'Cabang Pusat',
            ],
            [
                'address' => 'Jl. Merdeka No. 123, Jakarta',
                'is_active' => true,
            ]
        );

        $secondBranch = Branch::firstOrCreate(
            [
                'business_id' => $business->id,
                'name' => 'Cabang Subang',
            ],
            [
                'address' => 'Jl. Ahmad Yani No. 45, Subang',
                'is_active' => true,
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@erp.local'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'business_id' => $business->id,
                'branch_id' => null,
                'is_active' => true,
            ]
        )->syncRoles(['Owner']);

        User::firstOrCreate(
            ['email' => 'kasir1@erp.local'],
            [
                'name' => 'Kasir 1 - Cabang Pusat',
                'password' => Hash::make('password123'),
                'business_id' => $business->id,
                'branch_id' => $mainBranch->id,
                'is_active' => true,
            ]
        )->syncRoles(['Kasir']);

        User::firstOrCreate(
            ['email' => 'kasir2@erp.local'],
            [
                'name' => 'Kasir 2 - Cabang Subang',
                'password' => Hash::make('password123'),
                'business_id' => $business->id,
                'branch_id' => $secondBranch->id,
                'is_active' => true,
            ]
        )->syncRoles(['Kasir']);

        echo "✓ Owner user created/updated\n";
        echo "✓ Kasir 1 user created/updated (Cabang Pusat)\n";
        echo "✓ Kasir 2 user created/updated (Cabang Subang)\n";
        echo "✓ Business UMKM Toko Roti Berkah created/updated with 2 branches\n";
    }
}
