@extends('app.layouts.app')
@section('title', 'Edit Produk')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Edit Produk</h2>
        <form action="{{ route('app.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="space-y-4">
                <x-input label="Nama Produk" name="name" value="{{ old('name', $product->name) }}" required />
                <x-input label="SKU (opsional)" name="sku" value="{{ old('sku', $product->sku) }}" />
                <x-input label="Satuan (pcs, kg, dll)" name="base_unit" value="{{ old('base_unit', $product->base_unit) }}" required />
                <x-input label="Harga Jual (Rp)" name="selling_price" type="number" step="0.01" value="{{ old('selling_price', $product->selling_price) }}" required />
                <x-input label="Hasil per Resep" name="recipe_yield_quantity" type="number" step="0.01" min="0.01" value="{{ old('recipe_yield_quantity', $product->recipe_yield_quantity ?? 1) }}" helperText="Berapa unit produk jadi dari 1 kali proses resep?" />

                <div x-data="{
                    units: {{ json_encode(old('units', $product->units->map(function($u) { return ['id' => $u->id, 'unit_name' => $u->unit_name, 'conversion_to_base' => (string)$u->conversion_to_base, 'price_override' => $u->price_override ? (string)$u->price_override : '']; })->toArray())) }}
                }">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-foreground">Multi-Satuan</label>
                        <button type="button" @click="units.push({})" class="text-sm text-secondary hover:text-secondary/80">+ Tambah Satuan</button>
                    </div>
                    <template x-for="(unit, index) in units" :key="index">
                        <div class="flex gap-2 items-start mb-2 p-3 bg-muted rounded-[var(--radius)]">
                            <input type="hidden" :name="`units[${index}][id]`" x-model="unit.id">
                            <div class="flex-1">
                                <label class="text-xs text-muted-foreground">Nama</label>
                                <input type="text" :name="`units[${index}][unit_name]`" x-model="unit.unit_name" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" placeholder="mis. dus">
                            </div>
                            <div class="flex-1">
                                <label class="text-xs text-muted-foreground">Konversi</label>
                                <input type="number" step="0.01" :name="`units[${index}][conversion_to_base]`" x-model="unit.conversion_to_base" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" placeholder="mis. 12">
                            </div>
                            <div class="flex-1">
                                <label class="text-xs text-muted-foreground">Harga khusus</label>
                                <input type="number" step="0.01" :name="`units[${index}][price_override]`" x-model="unit.price_override" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                            </div>
                            <button type="button" @click="units.splice(index, 1)" class="mt-5 text-destructive text-sm">Hapus</button>
                        </div>
                    </template>
                </div>

                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Update</x-button>
                    <a href="{{ route('app.products.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
