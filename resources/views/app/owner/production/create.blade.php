@extends('app.layouts.app')
@section('title', 'Produksi Baru')
@section('content')
<div class="max-w-3xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Form Produksi Baru</h2>
        <form action="{{ route('app.production.store') }}" method="POST" x-data="{
            products: {{ json_encode($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'base_unit' => $p->base_unit])->values()) }},
            recipes: [],
            selectedRecipe: null,
            multiplier: 1,
            previewTotal: 0,
            previewItems: [],
            async loadRecipes(productId) {
                if (!productId) { this.recipes = []; this.selectedRecipe = null; return; }
                const resp = await fetch('{{ route('app.products.recipes.json', ['product' => '__ID__']) }}'.replace('__ID__', productId));
                this.recipes = await resp.json();
                this.selectedRecipe = null;
                this.previewTotal = 0;
                this.previewItems = [];
            },
            updatePreview() {
                const r = this.recipes.find(x => x.id == this.selectedRecipe);
                if (!r) { this.previewTotal = 0; this.previewItems = []; return; }
                this.previewTotal = r.yield_quantity * this.multiplier;
                this.previewItems = r.items.map(i => ({
                    name: i.raw_material_name,
                    qty: i.qty_per_batch * this.multiplier,
                    unit: i.unit,
                }));
            }
        }">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Produk <span class="text-destructive">*</span></label>
                    <select name="product_id" id="product-select" @change="loadRecipes($event.target.value)" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>{{ $p->name }} ({{ $p->base_unit }})</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="recipes.length > 0">
                    <label class="block text-sm font-medium text-foreground mb-1">Resep <span class="text-destructive">*</span></label>
                    <select name="recipe_id" x-model="selectedRecipe" @change="updatePreview()" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background" required>
                        <option value="">-- Pilih Resep --</option>
                        <template x-for="r in recipes" :key="r.id">
                            <option :value="r.id" x-text="r.name + ' (hasil: ' + r.yield_quantity + ' pcs)'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <x-input label="Cabang" name="branch_id" type="select" required>
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id')==$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </x-input>
                </div>

                <div>
                    <x-input label="Jumlah Pengulangan (batch_multiplier)" name="batch_multiplier" type="number" step="0.01" min="0.01" value="1" x-model="multiplier" @input="updatePreview()" helperText="Berapa kali resep ini dijalankan? Hasil akhir = yield_resep × multiplier." />
                </div>

                <x-input label="Tanggal Kedaluwarsa (opsional)" name="expired_date" type="date" value="{{ old('expired_date') }}" helperText="Akan tercatat di batch produk." />

                <div x-show="selectedRecipe && previewTotal > 0" class="bg-muted rounded-[var(--radius)] p-4 text-sm space-y-2">
                    <p class="font-semibold text-foreground">Preview Produksi</p>
                    <p>Akan menghasilkan: <strong x-text="previewTotal + ' pcs'">0</strong></p>
                    <div x-show="previewItems.length">
                        <p class="text-muted-foreground mt-2 mb-1">Kebutuhan bahan baku:</p>
                        <template x-for="item in previewItems" :key="item.name">
                            <div class="flex justify-between text-xs">
                                <span x-text="item.name"></span>
                                <span x-text="format_number(item.qty) + ' ' + item.unit"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <x-button type="submit" id="btn-submit">Jalankan Produksi</x-button>
                    <a href="{{ route('app.production.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
