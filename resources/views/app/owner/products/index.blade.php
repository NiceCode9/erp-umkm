@extends('app.layouts.app')
@section('title', 'Produk')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Produk</h2>
        <a href="{{ route('app.products.create') }}"><x-button>Tambah Produk</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">SKU</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Satuan</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Harga Jual</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Total Stok</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Multi-Satuan</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($products as $product)
                    @php $totalStock = (float) ($stockData[$product->id] ?? 0); @endphp
                    <tr class="hover:bg-muted/50 cursor-pointer" onclick="window.location='{{ route('app.products.show', $product) }}'">
                        <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $product->sku ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $product->base_unit }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($product->selling_price) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ format_number($totalStock) }}</td>
                        <td class="px-4 py-3">
                            @if($product->units->count())
                                @foreach($product->units as $unit)
                                    <span class="text-xs bg-muted px-2 py-0.5 rounded">{{ $unit->unit_name }} ({{ $unit->conversion_to_base }})</span>
                                @endforeach
                            @else<span class="text-muted-foreground">-</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('app.products.show', $product) }}"><x-button variant="secondary" size="sm">Detail</x-button></a>
                                <a href="{{ route('app.products.recipes.index', $product) }}"><x-button variant="info" size="sm">Resep</x-button></a>
                                <a href="{{ route('app.products.edit', $product) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
                                <form action="{{ route('app.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">@csrf @method('DELETE')<x-button variant="danger" size="sm" type="submit">Hapus</x-button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($products->hasPages())<div class="mt-4">{{ $products->links() }}</div>@endif
</x-card>
@endsection
