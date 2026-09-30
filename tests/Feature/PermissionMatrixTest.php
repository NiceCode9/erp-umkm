<?php

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Hak akses Owner atas Cabang & Kasir
|--------------------------------------------------------------------------
|
| Keputusan final (PERMISSIONS.md): Superadmin adalah SATU-SATUNYA yang bisa
| menambah cabang & membuat akun Kasir. Owner hanya boleh MENGUBAH data yang
| sudah ada milik business-nya sendiri, termasuk reset password Kasir.
|
*/

/*
|--------------------------------------------------------------------------
| 1. Route create milik Superadmin saja -> Owner 403
|--------------------------------------------------------------------------
*/

test('owner is forbidden from opening every branch creation route', function (string $method, string $uri) {
    $owner = makeOwner();

    $this->actingAs($owner)
        ->$method($uri)
        ->assertForbidden();
})->with([
    'GET branches/create' => ['get', '/app/branches/create'],
    'POST branches' => ['post', '/app/branches'],
]);

test('owner is forbidden from opening every kasir creation route', function (string $method, string $uri) {
    $owner = makeOwner();

    $this->actingAs($owner)
        ->$method($uri)
        ->assertForbidden();
})->with([
    'GET kasir/create' => ['get', '/app/kasir/create'],
    'POST kasir' => ['post', '/app/kasir'],
]);

test('owner is forbidden from deleting a branch', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);

    $this->actingAs($owner)
        ->delete("/app/branches/{$branch->id}")
        ->assertForbidden();

    expect($branch->fresh())->not->toBeNull();
});

test('owner never sees a create button on branch or kasir list', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    makeKasir($owner->business, $branch);

    $this->actingAs($owner)->get(route('app.branches.index'))
        ->assertOk()
        ->assertDontSee('Tambah Cabang')
        ->assertDontSee('href="'.route('app.branches.create').'"');

    $this->actingAs($owner)->get(route('app.kasir.index'))
        ->assertOk()
        ->assertDontSee('Tambah Kasir')
        ->assertDontSee('href="'.route('app.kasir.create').'"');
});

/*
|--------------------------------------------------------------------------
| 2. Owner tetap boleh mengedit miliknya sendiri
|--------------------------------------------------------------------------
*/

test('owner can view and update their own branch', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business, 'Cabang Milik Sendiri');

    $this->actingAs($owner)
        ->get(route('app.branches.edit', $branch))
        ->assertOk();

    $this->actingAs($owner)
        ->put(route('app.branches.update', $branch), [
            'name' => 'Cabang Sudah Diubah',
            'address' => 'Alamat baru',
        ])
        ->assertSessionHasNoErrors();

    expect($branch->refresh()->name)->toBe('Cabang Sudah Diubah');
});

test('owner can view, update and reset password of their own kasir', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $kasir = makeKasir($owner->business, $branch);

    $this->actingAs($owner)->get(route('app.kasir.edit', $kasir))->assertOk();

    $this->actingAs($owner)->put(route('app.kasir.update', $kasir), [
        'name' => 'Kasir Baru',
        'email' => $kasir->email,
        'branch_id' => $branch->id,
    ])->assertSessionHasNoErrors();

    $this->actingAs($owner)->get(route('app.kasir.reset-password.form', $kasir))->assertOk();

    $this->actingAs($owner)->post(route('app.kasir.reset-password', $kasir), [
        'password' => 'rahasia-baru-123',
        'password_confirmation' => 'rahasia-baru-123',
    ])->assertSessionHasNoErrors();

    expect(Illuminate\Support\Facades\Hash::check('rahasia-baru-123', $kasir->refresh()->password))->toBeTrue();
});

test('owner cannot move a kasir into a branch of another business', function () {
    $ownerA = makeOwner();
    $branchA = makeBranchFor($ownerA->business, 'Cabang A');
    $kasir = makeKasir($ownerA->business, $branchA);

    $ownerB = makeOwner();
    $branchB = makeBranchFor($ownerB->business, 'Cabang B');

    $this->actingAs($ownerA)
        ->put(route('app.kasir.update', $kasir), [
            'name' => $kasir->name,
            'email' => $kasir->email,
            'branch_id' => $branchB->id,
        ])
        ->assertNotFound();

    expect($kasir->refresh()->branch_id)->toBe($branchA->id);
});

/*
|--------------------------------------------------------------------------
| 3. Isolasi antar tenant
|--------------------------------------------------------------------------
|
| Cabang memakai global scope BelongsToBusiness, jadi binding {branch} milik
| tenant lain tidak ditemukan sama sekali -> 404.
| User TIDAK memakai global scope (AGENTS.md 2.1), jadi binding {kasir} berhasil
| lalu ditolak policy -> 403.
|
*/

test('owner cannot reach a branch belonging to another business', function () {
    $ownerA = makeOwner();
    makeBranchFor($ownerA->business, 'Cabang A');

    $ownerB = makeOwner();
    $branchB = makeBranchFor($ownerB->business, 'Cabang B');

    $this->actingAs($ownerA)
        ->get(route('app.branches.edit', $branchB))
        ->assertNotFound();

    $this->actingAs($ownerA)
        ->put(route('app.branches.update', $branchB), ['name' => 'Dibajak'])
        ->assertNotFound();

    expect($branchB->refresh()->name)->toBe('Cabang B');
});

test('owner cannot reach a kasir belonging to another business', function () {
    $ownerA = makeOwner();
    makeBranchFor($ownerA->business, 'Cabang A');

    $ownerB = makeOwner();
    $branchB = makeBranchFor($ownerB->business, 'Cabang B');
    $kasirB = makeKasir($ownerB->business, $branchB);

    $this->actingAs($ownerA)
        ->get(route('app.kasir.edit', $kasirB))
        ->assertForbidden();

    $this->actingAs($ownerA)
        ->get(route('app.kasir.reset-password.form', $kasirB))
        ->assertForbidden();

    $this->actingAs($ownerA)
        ->post(route('app.kasir.reset-password', $kasirB), [
            'password' => 'password-dibajak',
            'password_confirmation' => 'password-dibajak',
        ])
        ->assertForbidden();

    expect(Illuminate\Support\Facades\Hash::check('password-dibajak', $kasirB->refresh()->password))->toBeFalse();
});

test('owner cannot see another business kasir in the list', function () {
    $ownerA = makeOwner();
    makeBranchFor($ownerA->business, 'Cabang A');

    $ownerB = makeOwner();
    $branchB = makeBranchFor($ownerB->business, 'Cabang B');
    makeKasir($ownerB->business, $branchB, 'Kasir Milik Business B');

    $this->actingAs($ownerA)
        ->get(route('app.kasir.index'))
        ->assertOk()
        ->assertDontSee('Kasir Milik Business B');
});

/*
|--------------------------------------------------------------------------
| 4. Pemisahan area: Superadmin tidak masuk ke /app/*
|--------------------------------------------------------------------------
*/

test('superadmin is forbidden from the owner area', function () {
    $superadmin = makeSuperadmin();

    $this->actingAs($superadmin)->get(route('app.branches.index'))->assertForbidden();
    $this->actingAs($superadmin)->get(route('app.kasir.index'))->assertForbidden();
    $this->actingAs($superadmin)->get(route('app.dashboard'))->assertForbidden();
});

test('kasir is forbidden from the owner area', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $kasir = makeKasir($owner->business, $branch);

    $this->actingAs($kasir)->get(route('app.branches.index'))->assertForbidden();
    $this->actingAs($kasir)->get(route('app.kasir.index'))->assertForbidden();
    $this->actingAs($kasir)->get(route('app.raw-materials.index'))->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| 5. UserPolicy
|--------------------------------------------------------------------------
*/

test('user policy denies kasir that is not in the same business', function () {
    $ownerA = makeOwner();
    makeBranchFor($ownerA->business, 'Cabang A');

    $ownerB = makeOwner();
    $branchB = makeBranchFor($ownerB->business, 'Cabang B');
    $kasirB = makeKasir($ownerB->business, $branchB);

    $policy = new UserPolicy();

    expect($policy->update($ownerA, $kasirB)->denied())->toBeTrue();
    expect($policy->resetPassword($ownerA, $kasirB)->denied())->toBeTrue();
    expect($policy->view($ownerA, $kasirB)->denied())->toBeTrue();
    expect($policy->delete($ownerA, $kasirB)->denied())->toBeTrue();
});

test('user policy allows owner to manage their own kasir', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $kasir = makeKasir($owner->business, $branch);

    $policy = new UserPolicy();

    expect($policy->update($owner, $kasir)->allowed())->toBeTrue();
    expect($policy->resetPassword($owner, $kasir)->allowed())->toBeTrue();
});

test('user policy refuses to treat an owner account as a kasir', function () {
    $owner = makeOwner();
    $otherOwner = User::factory()->create([
        'business_id' => $owner->business_id,
        'is_active' => true,
    ]);
    $otherOwner->assignRole('Owner');

    $policy = new UserPolicy();

    expect($policy->update($owner, $otherOwner)->denied())->toBeTrue();
    expect($policy->resetPassword($owner, $otherOwner)->denied())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| 6. Integritas RolePermissionSeeder
|--------------------------------------------------------------------------
*/

test('superadmin receives every permission defined by the seeder', function () {
    seedPermissions();

    $all = Permission::where('guard_name', 'web')->pluck('name');
    $granted = Role::findByName('Superadmin')->permissions->pluck('name');

    expect($granted->sort()->values()->all())
        ->toBe($all->sort()->values()->all());
});

test('owner never receives provisioning permissions', function () {
    seedPermissions();

    $owner = Role::findByName('Owner');

    foreach (['create-branches', 'create-kasir', 'delete-branches'] as $forbidden) {
        expect($owner->hasPermissionTo($forbidden))->toBeFalse();
    }

    foreach (['view-branches', 'edit-branches', 'edit-kasir', 'reset-kasir-password'] as $allowed) {
        expect($owner->hasPermissionTo($allowed))->toBeTrue();
    }
});

test('retired permissions are removed from the database', function () {
    seedPermissions();

    foreach (['manage-branches', 'manage-users'] as $retired) {
        expect(Permission::where('name', $retired)->exists())->toBeFalse();
    }
});

test('seeder permission list has no duplicate entries', function () {
    $source = file_get_contents(app_path('../database/seeders/RolePermissionSeeder.php'));

    preg_match('/\$permissions = \[(.*?)\];/s', $source, $m);

    preg_match_all("/'([a-z0-9-]+)'/", $m[1], $names);
    $names = $names[1];

    expect($names)->toBe(array_values(array_unique($names)));
    expect(count($names))->toBeGreaterThan(30);
});
