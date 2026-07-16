@extends('superadmin.layouts.app')

@section('title', $business->name)

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ tab: 'branches' }">
    <x-card>
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-bold">{{ $business->name }}</h2>
                <p class="text-sm text-muted-foreground">{{ $business->phone }} — {{ $business->address }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($business->is_active)
                    <x-badge variant="success">Aktif</x-badge>
                @else
                    <x-badge variant="danger">Nonaktif</x-badge>
                @endif
                <a href="{{ route('superadmin.businesses.edit', $business) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 text-sm border-t border-border pt-4">
            <div><span class="text-muted-foreground">Owner:</span> <strong>{{ $business->owner_name }}</strong></div>
            <div><span class="text-muted-foreground">Cabang:</span> <strong>{{ $business->branches->count() }}</strong></div>
            <div><span class="text-muted-foreground">Users:</span> <strong>{{ $business->users->count() }}</strong></div>
        </div>
    </x-card>

    <!-- Tab buttons -->
    <div class="flex gap-2 mt-4 mb-2">
        <button @click="tab = 'branches'"
            :class="tab === 'branches' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'"
            class="px-4 py-2 rounded-[var(--radius)] text-sm font-medium transition">Cabang ({{ $business->branches->count() }})</button>
        <button @click="tab = 'users'"
            :class="tab === 'users' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'"
            class="px-4 py-2 rounded-[var(--radius)] text-sm font-medium transition">User ({{ $business->users->count() }})</button>
    </div>

    <!-- Cabang Tab -->
    <div x-show="tab === 'branches'" x-cloak>
        <x-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold">Daftar Cabang</h3>
                <a href="{{ route('superadmin.businesses.branches.create', $business) }}"><x-button size="sm">+ Tambah Cabang</x-button></a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Alamat</th>
                            <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($business->branches as $branch)
                            <tr class="hover:bg-muted/50">
                                <td class="px-4 py-3 font-medium">{{ $branch->name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $branch->address ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($branch->is_active)<x-badge variant="success">Aktif</x-badge>
                                    @else<x-badge variant="danger">Nonaktif</x-badge>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-muted-foreground">Belum ada cabang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Users Tab -->
    <div x-show="tab === 'users'" x-cloak>
        <div class="flex gap-3 mb-4">
            <a href="{{ route('superadmin.businesses.owners.create', $business) }}"><x-button size="sm">+ Tambah Owner</x-button></a>
            <a href="{{ route('superadmin.businesses.kasir.create', $business) }}"><x-button size="sm">+ Tambah Kasir</x-button></a>
        </div>
        <x-card>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Email</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Role</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                            <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($business->users as $u)
                            <tr class="hover:bg-muted/50">
                                <td class="px-4 py-3 font-medium">{{ $u->name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $u->email }}</td>
                                <td class="px-4 py-3">
                                    @foreach($u->roles as $role)
                                        <x-badge variant="{{ $role->name === 'Owner' ? 'primary' : 'default' }}">{{ $role->name }}</x-badge>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $u->branch->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($u->is_active)<x-badge variant="success">Aktif</x-badge>
                                    @else<x-badge variant="danger">Nonaktif</x-badge>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Belum ada user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection
