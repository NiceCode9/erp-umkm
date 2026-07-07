@extends('superadmin.layouts.app')

@section('title', 'Dashboard Superadmin')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <x-card>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-muted-foreground text-sm mb-1">Total Tenant</p>
                <p class="text-2xl font-bold text-foreground">{{ $total_businesses }}</p>
            </div>
            <div class="text-primary text-3xl opacity-20">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-muted-foreground text-sm mb-1">Tenant Aktif</p>
                <p class="text-2xl font-bold text-primary">{{ $active_businesses }}</p>
            </div>
            <div class="text-primary text-3xl opacity-20">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-muted-foreground text-sm mb-1">Tenant Nonaktif</p>
                <p class="text-2xl font-bold text-destructive">{{ $inactive_businesses }}</p>
            </div>
            <div class="text-destructive text-3xl opacity-20">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </x-card>
</div>

<x-card class="mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-foreground">Daftar Tenant</h2>
        <a href="{{ route('superadmin.businesses.create') }}">
            <x-button>Tambah Business</x-button>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-foreground">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama UMKM</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Pemilik</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Telepon</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($businesses as $business)
                    <tr class="hover:bg-muted/50 transition">
                        <td class="px-4 py-3">{{ $business->id }}</td>
                        <td class="px-4 py-3">{{ $business->name }}</td>
                        <td class="px-4 py-3">{{ $business->owner_name }}</td>
                        <td class="px-4 py-3">{{ $business->phone }}</td>
                        <td class="px-4 py-3">
                            @if ($business->is_active)
                                <x-badge variant="success">Aktif</x-badge>
                            @else
                                <x-badge variant="danger">Nonaktif</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('superadmin.businesses.edit', $business) }}">
                                    <x-button variant="secondary" size="sm">Edit</x-button>
                                </a>
                                @if($business->is_active)
                                    <form action="{{ route('superadmin.businesses.deactivate', $business) }}" method="POST" class="inline">
                                        @csrf
                                        <x-button variant="danger" size="sm" type="submit" onclick="return confirm('Nonaktifkan business ini?')">Nonaktifkan</x-button>
                                    </form>
                                @else
                                    <form action="{{ route('superadmin.businesses.activate', $business) }}" method="POST" class="inline">
                                        @csrf
                                        <x-button variant="primary" size="sm" type="submit" onclick="return confirm('Aktifkan business ini?')">Aktifkan</x-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada data tenant</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
