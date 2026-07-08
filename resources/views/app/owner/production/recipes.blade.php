@extends('app.layouts.app')
@section('title', 'Resep: ' . $product->name)
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-card>
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">Resep Produksi: {{ $product->name }}</h2>
                <p class="text-sm text-muted-foreground">Satuan dasar: {{ $product->base_unit }}</p>
            </div>
            <a href="{{ route('app.products.index') }}"><x-button variant="secondary" size="sm">Kembali</x-button></a>
        </div>

        <div class="mb-6 p-4 bg-muted rounded-[var(--radius)] text-sm">
            <strong class="text-foreground">Pengaturan Resep</strong>
            <form action="{{ route('app.products.update', $product) }}" method="POST" class="mt-2">
                @csrf @method('PUT')
                <input type="hidden" name="name" value="{{ $product->name }}">
                <input type="hidden" name="base_unit" value="{{ $product->base_unit }}">
                <input type="hidden" name="selling_price" value="{{ $product->selling_price }}">
                <div class="flex items-center gap-3">
                    <label class="text-muted-foreground whitespace-nowrap">1 kali proses resep menghasilkan:</label>
                    <input type="number" name="recipe_yield_quantity" value="{{ old('recipe_yield_quantity', $product->recipe_yield_quantity ?? 1) }}" step="0.01" min="0.01" class="w-24 border border-input rounded-[var(--radius)] px-3 py-1.5 text-sm bg-background" required>
                    <span class="text-muted-foreground">{{ $product->base_unit }}</span>
                    <x-button size="sm" type="submit">Simpan</x-button>
                </div>
                <p class="text-xs text-muted-foreground mt-1">Contoh: jika 1 resep adonan menghasilkan 20 roti, isi dengan 20.</p>
            </form>
        </div>

        <h3 class="text-sm font-semibold text-foreground mb-3">Komposisi Bahan Baku (per batch resep)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Bahan Baku</th>
                        <th class="px-3 py-2 text-right text-xs text-muted-foreground">Qty per Batch</th>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Satuan</th>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">per {{ $product->base_unit }}</th>
                        <th class="px-3 py-2 text-center text-xs text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($product->recipes as $r)
                        @php $perUnit = $product->recipe_yield_quantity > 0 ? $r->qty_per_batch / $product->recipe_yield_quantity : 0; @endphp
                        <tr>
                            <td class="px-3 py-2">{{ $r->rawMaterial->name }}</td>
                            <td class="px-3 py-2 text-right font-medium">{{ format_number($r->qty_per_batch) }}</td>
                            <td class="px-3 py-2">{{ $r->unit }}</td>
                            <td class="px-3 py-2 text-muted-foreground">= {{ format_number($perUnit) }} {{ $r->unit }}</td>
                            <td class="px-3 py-2 text-center">
                                <form action="{{ route('app.products.recipes.destroy', [$product, $r]) }}" method="POST" onsubmit="return confirm('Hapus bahan ini dari resep?')" class="inline">@csrf @method('DELETE')<x-button variant="danger" size="sm" type="submit">Hapus</x-button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-4 text-center text-muted-foreground">Belum ada resep.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card>
        <h3 class="text-sm font-semibold text-foreground mb-4">Tambah Bahan ke Resep</h3>
        <form action="{{ route('app.products.recipes.store', $product) }}" method="POST">
            @csrf
            <x-form-repeater name="recipes" label="Bahan Baku" addLabel="+ Tambah Bahan" :minItems="1" emptyMessage="Belum ada bahan.">
                <div>
                    <label class="text-xs text-muted-foreground">Bahan Baku</label>
                    <select :name="`recipes[${index}][raw_material_id]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                        <option value="">-- Pilih --</option>
                        @foreach($rawMaterials as $rm)
                            <option value="{{ $rm->id }}">{{ $rm->name }} ({{ $rm->base_unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Qty per Batch</label>
                    <input type="number" step="0.01" min="0.01" :name="`recipes[${index}][qty_per_batch]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Satuan</label>
                    <input type="text" :name="`recipes[${index}][unit]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" placeholder="g, kg, ml" required>
                </div>
            </x-form-repeater>
            <div class="mt-4"><x-button type="submit">Simpan Semua Bahan</x-button></div>
        </form>
    </x-card>

    <div class="text-xs text-muted-foreground p-4 bg-muted rounded-[var(--radius)]">
        <strong>Skema yield-based:</strong> Resep didefinisikan per SATU KALI PROSES (batch). Sistem menghitung otomatis kebutuhan per unit = qty_per_batch &divide; recipe_yield_quantity. Konversi satuan g&harr;kg, ml&harr;liter otomatis saat produksi.
    </div>
</div>
@endsection
