@extends('app.layouts.app')
@section('title', 'Resep: ' . $product->name)
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <x-card>
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-lg font-semibold">Daftar Resep: {{ $product->name }}</h2>
            <a href="{{ route('app.products.index') }}"><x-button variant="secondary" size="sm">Kembali</x-button></a>
        </div>
        <p class="text-sm text-muted-foreground mb-4">Satuan dasar: {{ $product->base_unit }}. Satu produk bisa punya banyak resep dengan yield berbeda.</p>

        @if($product->recipes->count())
            <div class="space-y-4">
                @foreach($product->recipes as $recipe)
                    <div class="border border-border rounded-[var(--radius)] p-4 bg-card">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="font-semibold text-foreground">{{ $recipe->name }}</h3>
                                <p class="text-xs text-muted-foreground">
                                    Yield: {{ format_number($recipe->yield_quantity) }} {{ $product->base_unit }}
                                    — {{ $recipe->items_count }} bahan
                                    — {{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <form action="{{ route('app.products.recipes.toggle', [$product, $recipe]) }}" method="POST" class="inline">
                                    @csrf
                                    @if($recipe->is_active)
                                        <x-button variant="warning" size="sm" type="submit">Nonaktifkan</x-button>
                                    @else
                                        <x-button variant="primary" size="sm" type="submit">Aktifkan</x-button>
                                    @endif
                                </form>
                                <form action="{{ route('app.products.recipes.destroy', [$product, $recipe]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus resep {{ $recipe->name }}?')">
                                    @csrf @method('DELETE')
                                    <x-button variant="danger" size="sm" type="submit">Hapus</x-button>
                                </form>
                            </div>
                        </div>
                        <table class="w-full text-xs">
                            <thead class="bg-muted border-b border-border">
                                <tr>
                                    <th class="px-2 py-1 text-left text-muted-foreground">Bahan</th>
                                    <th class="px-2 py-1 text-right text-muted-foreground">Qty per Batch</th>
                                    <th class="px-2 py-1 text-left text-muted-foreground">Satuan</th>
                                    <th class="px-2 py-1 text-left text-muted-foreground">Stok Dasar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach($recipe->items as $item)
                                    <tr>
                                        <td class="px-2 py-1">{{ $item->rawMaterial->name }}</td>
                                        <td class="px-2 py-1 text-right">{{ format_number($item->qty_per_batch) }}</td>
                                        <td class="px-2 py-1">{{ $item->unit }}</td>
                                        <td class="px-2 py-1">{{ $item->rawMaterial->base_unit }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-muted-foreground py-6 text-center">Belum ada resep. Tambah resep baru di bawah.</p>
        @endif
    </x-card>

    <x-card>
        <h3 class="text-sm font-semibold text-foreground mb-4">Tambah Resep Baru</h3>
        <form action="{{ route('app.products.recipes.store', $product) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <x-input label="Nama Resep" name="name" value="{{ old('name') }}" placeholder="Mis: Resep 100 pcs" required />
                <x-input label="Yield (jumlah hasil)" name="yield_quantity" type="number" step="0.01" min="0.01" value="{{ old('yield_quantity') }}" helperText="Berapa {{ $product->base_unit }} dari 1 kali proses?" required />
            </div>

            <h4 class="text-sm font-medium text-foreground mb-3">Bahan Baku</h4>
            <x-form-repeater name="items" addLabel="+ Tambah Bahan" :minItems="1" emptyMessage="Belum ada bahan.">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs text-muted-foreground">Bahan Baku</label>
                        <select :name="`items[${index}][raw_material_id]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                            <option value="">-- Pilih --</option>
                            @foreach($rawMaterials as $rm)
                                <option value="{{ $rm->id }}">{{ $rm->name }} ({{ $rm->base_unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-muted-foreground">Qty per Batch</label>
                        <input type="number" step="0.01" min="0.01" :name="`items[${index}][qty_per_batch]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                    </div>
                    <div>
                        <label class="text-xs text-muted-foreground">Satuan</label>
                        <input type="text" :name="`items[${index}][unit]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" placeholder="g, kg" required>
                    </div>
                </div>
            </x-form-repeater>
            <div class="mt-4"><x-button type="submit">Simpan Resep</x-button></div>
        </form>
    </x-card>
</div>
@endsection
