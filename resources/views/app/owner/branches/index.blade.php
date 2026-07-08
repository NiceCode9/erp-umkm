@extends('app.layouts.app')
@section('title', 'Kelola Cabang')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Daftar Cabang</h2>
        <a href="{{ route('app.branches.create') }}"><x-button>Tambah Cabang</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Alamat</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($branches as $branch)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $branch->name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $branch->address }}</td>
                        <td class="px-4 py-3">{!! $branch->is_active ? '<x-badge variant="success">Aktif</x-badge>' : '<x-badge variant="danger">Nonaktif</x-badge>' !!}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('app.branches.edit', $branch) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
                                <a href="{{ route('app.branches.settings.edit', $branch) }}"><x-button variant="secondary" size="sm">Pajak</x-button></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-muted-foreground">Belum ada cabang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($branches->hasPages())<div class="mt-4">{{ $branches->links() }}</div>@endif
</x-card>
@endsection
