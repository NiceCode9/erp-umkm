@extends('superadmin.layouts.app')

@section('title', 'Kelola Business')

@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-foreground">Daftar Business (Tenant)</h2>
        <a href="{{ route('superadmin.businesses.create') }}">
            <x-button>Tambah Business + Owner</x-button>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-foreground">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama UMKM</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Pemilik</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Dibuat</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($businesses as $business)
                    <tr class="hover:bg-muted/50 transition">
                        <td class="px-4 py-3 font-medium">{{ $business->name }}</td>
                        <td class="px-4 py-3">{{ $business->owner_name }}</td>
                        <td class="px-4 py-3">{{ $business->branches_count }}</td>
                        <td class="px-4 py-3">
                            @if($business->is_active)
                                <x-badge variant="success">Aktif</x-badge>
                            @else
                                <x-badge variant="danger">Nonaktif</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $business->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('superadmin.businesses.show', $business) }}">
                                    <x-button size="sm">Detail</x-button>
                                </a>
                                <a href="{{ route('superadmin.businesses.edit', $business) }}">
                                    <x-button variant="secondary" size="sm">Edit</x-button>
                                </a>
                                @if($business->is_active)
                                    <x-button
                                        variant="danger"
                                        size="sm"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-deactivate-{{ $business->id }}')"
                                    >Nonaktifkan</x-button>
                                @else
                                    <x-button
                                        variant="primary"
                                        size="sm"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-activate-{{ $business->id }}')"
                                    >Aktifkan</x-button>
                                @endif
                            </div>
                        </td>
                    </tr>

                    @if($business->is_active)
                        <x-modal name="confirm-deactivate-{{ $business->id }}" :show="false" focusable>
                            <form action="{{ route('superadmin.businesses.deactivate', $business) }}" method="POST">
                                @csrf
                                <div class="p-6 text-center">
                                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-destructive/10">
                                        <svg class="h-8 w-8 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                    </div>
                                    <h2 class="text-xl font-semibold text-foreground mb-2">Nonaktifkan Business</h2>
                                    <p class="text-sm text-muted-foreground mb-4 max-w-sm mx-auto">
                                        Business <strong class="text-foreground">{{ $business->name }}</strong> akan dinonaktifkan. Seluruh Owner & Kasir tidak bisa login selama business nonaktif.
                                    </p>

                                    <div class="bg-muted rounded-[var(--radius)] p-4 mb-6 text-left text-sm space-y-1.5">
                                        <div class="flex justify-between"><span class="text-muted-foreground">Nama UMKM</span><span class="font-medium text-foreground">{{ $business->name }}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Pemilik</span><span class="font-medium text-foreground">{{ $business->owner_name }}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Status saat ini</span><span><x-badge variant="success">Aktif</x-badge></span></div>
                                    </div>

                                    <p class="text-xs text-warning mb-6">Tindakan ini bisa dibalikkan. Anda bisa mengaktifkannya kembali kapan saja.</p>
                                </div>
                                <div class="border-t border-border px-6 py-4 flex justify-end gap-3 bg-muted/50 rounded-b-[var(--radius)]">
                                    <x-button variant="secondary" type="button" x-on:click="$dispatch('close')">Batal</x-button>
                                    <x-button variant="danger" type="submit">Ya, Nonaktifkan</x-button>
                                </div>
                            </form>
                        </x-modal>
                    @else
                        <x-modal name="confirm-activate-{{ $business->id }}" :show="false" focusable>
                            <form action="{{ route('superadmin.businesses.activate', $business) }}" method="POST">
                                @csrf
                                <div class="p-6 text-center">
                                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h2 class="text-xl font-semibold text-foreground mb-2">Aktifkan Business</h2>
                                    <p class="text-sm text-muted-foreground mb-4 max-w-sm mx-auto">
                                        Business <strong class="text-foreground">{{ $business->name }}</strong> akan diaktifkan kembali. Owner & Kasir bisa login seperti biasa.
                                    </p>

                                    <div class="bg-muted rounded-[var(--radius)] p-4 mb-6 text-left text-sm space-y-1.5">
                                        <div class="flex justify-between"><span class="text-muted-foreground">Nama UMKM</span><span class="font-medium text-foreground">{{ $business->name }}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Pemilik</span><span class="font-medium text-foreground">{{ $business->owner_name }}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Status saat ini</span><span><x-badge variant="danger">Nonaktif</x-badge></span></div>
                                    </div>
                                </div>
                                <div class="border-t border-border px-6 py-4 flex justify-end gap-3 bg-muted/50 rounded-b-[var(--radius)]">
                                    <x-button variant="secondary" type="button" x-on:click="$dispatch('close')">Batal</x-button>
                                    <x-button type="submit">Ya, Aktifkan</x-button>
                                </div>
                            </form>
                        </x-modal>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada data business</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($businesses->hasPages())
        <div class="mt-4">
            {{ $businesses->links() }}
        </div>
    @endif
</x-card>
@endsection
