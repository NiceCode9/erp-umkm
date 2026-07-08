@extends('app.layouts.app')
@section('title', 'Supplier')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Daftar Supplier</h2>
        <a href="{{ route('app.suppliers.create') }}"><x-button>Tambah Supplier</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Telepon</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Alamat</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $supplier->name }}</td>
                        <td class="px-4 py-3">{{ $supplier->phone }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $supplier->address }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('app.suppliers.edit', $supplier) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
                                <form action="{{ route('app.suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Hapus supplier ini?')">@csrf @method('DELETE')<x-button variant="danger" size="sm" type="submit">Hapus</x-button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-muted-foreground">Belum ada supplier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($suppliers->hasPages())<div class="mt-4">{{ $suppliers->links() }}</div>@endif
</x-card>
@endsection
