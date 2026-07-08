@extends('app.layouts.app')
@section('title', 'Kelola Kasir')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Daftar Kasir</h2>
        <a href="{{ route('app.kasir.create') }}"><x-button>Tambah Kasir</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Email</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($kasir as $user)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->branch->name ?? '-' }}</td>
                        <td class="px-4 py-3">{!! $user->is_active ? '<x-badge variant="success">Aktif</x-badge>' : '<x-badge variant="danger">Nonaktif</x-badge>' !!}</td>
                        <td class="px-4 py-3"><a href="{{ route('app.kasir.edit', $user) }}"><x-button variant="secondary" size="sm">Edit</x-button></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Belum ada Kasir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($kasir->hasPages())<div class="mt-4">{{ $kasir->links() }}</div>@endif
</x-card>
@endsection
