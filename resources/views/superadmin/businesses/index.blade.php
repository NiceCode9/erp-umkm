@extends('superadmin.layouts.app')

@section('title', 'Kelola Business')

@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-foreground">Daftar Business (Tenant)</h2>
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
                @forelse($businesses as $business)
                    <tr class="hover:bg-muted/50 transition">
                        <td class="px-4 py-3">{{ $business->id }}</td>
                        <td class="px-4 py-3">{{ $business->name }}</td>
                        <td class="px-4 py-3">{{ $business->owner_name }}</td>
                        <td class="px-4 py-3">{{ $business->phone }}</td>
                        <td class="px-4 py-3">
                            @if($business->is_active)
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
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada data business</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
