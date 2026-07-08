@extends('app.layouts.app')
@section('title', 'Bahan Baku')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Bahan Baku</h2>
        <a href="{{ route('app.raw-materials.create') }}"><x-button>Tambah Bahan Baku</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Bahan Baku</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Satuan</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Total Stok</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Min. Stok</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($materialData as $item)
                    <tr class="hover:bg-muted/50 cursor-pointer" onclick="window.location='{{ route('app.raw-materials.show', $item->id) }}'">
                        <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-3">{{ $item->base_unit }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ format_number($item->total_stock) }}</td>
                        <td class="px-4 py-3">{{ $item->minimum_stock }}</td>
                        <td class="px-4 py-3">
                            @if($item->low_stock_branches > 0)
                                <x-badge variant="warning">{{ $item->low_stock_branches }} cabang stok rendah</x-badge>
                            @elseif($item->total_stock == 0)
                                <x-badge variant="danger">Stok kosong</x-badge>
                            @else
                                <x-badge variant="success">Aman</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('app.raw-materials.show', $item->id) }}"><x-button variant="secondary" size="sm">Detail</x-button></a>
                                <a href="{{ route('app.raw-materials.edit', $item->id) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
                                <form action="{{ route('app.raw-materials.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus bahan baku ini?')">@csrf @method('DELETE')<x-button variant="danger" size="sm" type="submit">Hapus</x-button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada bahan baku.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
