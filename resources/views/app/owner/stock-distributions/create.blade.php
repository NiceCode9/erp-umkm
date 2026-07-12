@extends('app.layouts.app')
@section('title', 'Distribusi Stok')
@push('scripts')
<script>
    let itemIdx = 0;
    function addItem() {
        const container = document.getElementById('items-list');
        const idx = itemIdx++;
        const rawOptions = document.getElementById('raw-options').innerHTML;
        const prodOptions = document.getElementById('prod-options').innerHTML;
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-start p-3 bg-muted rounded-[var(--radius)] dist-item';
        div.innerHTML = `
            <div class="w-32">
                <select name="items[${idx}][item_type]" class="item-type w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm" onchange="toggleItemSelect(this, ${idx})" required>
                    <option value="raw_material">Bahan Baku</option>
                    <option value="product">Produk Jadi</option>
                </select>
            </div>
            <div class="flex-1">
                <select name="items[${idx}][item_id]" class="item-select w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm" required>
                    <option value="">Pilih item</option>
                    <optgroup label="Bahan Baku" class="raw-group">${rawOptions}</optgroup>
                    <optgroup label="Produk Jadi" class="prod-group hidden">${prodOptions}</optgroup>
                </select>
            </div>
            <div class="w-24">
                <input type="number" name="items[${idx}][quantity]" class="w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm text-center" min="0.01" step="0.01" placeholder="Qty" required>
            </div>
            <button type="button" onclick="this.closest('.dist-item').remove()" class="px-2 py-2 text-destructive hover:text-destructive/80">×</button>
        `;
        container.appendChild(div);
    }

    function toggleItemSelect(select, idx) {
        const isProduct = select.value === 'product';
        const item = select.closest('.dist-item');
        item.querySelectorAll('.raw-group').forEach(g => g.classList.toggle('hidden', isProduct));
        item.querySelectorAll('.prod-group').forEach(g => g.classList.toggle('hidden', !isProduct));
    }
</script>
@endpush
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <x-card>
        <h2 class="text-2xl font-bold text-foreground mb-6">Distribusi Stok Antar Cabang</h2>

        <form method="POST" action="{{ route('app.owner.stock-distributions.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="origin_branch_id" value="Cabang Asal" />
                    <select id="origin_branch_id" name="origin_branch_id" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" required>
                        <option value="">Pilih Cabang Asal</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('origin_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('origin_branch_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="destination_branch_id" value="Cabang Tujuan" />
                    <select id="destination_branch_id" name="destination_branch_id" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" required>
                        <option value="">Pilih Cabang Tujuan</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('destination_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('destination_branch_id')" class="mt-1" />
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-foreground">Item yang Didistribusikan</p>
                    <button type="button" onclick="addItem()" class="text-sm text-secondary hover:text-secondary/80">+ Tambah Item</button>
                </div>

                <div id="items-list" class="space-y-2">
                    @if(old('items'))
                        @foreach(old('items') as $idx => $item)
                            <div class="flex gap-2 items-start p-3 bg-muted rounded-[var(--radius)] dist-item">
                                <div class="w-32">
                                    <select name="items[{{ $idx }}][item_type]" class="item-type w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm" onchange="toggleItemSelect(this, {{ $idx }})" required>
                                        <option value="raw_material" {{ ($item['item_type'] ?? '') === 'raw_material' ? 'selected' : '' }}>Bahan Baku</option>
                                        <option value="product" {{ ($item['item_type'] ?? '') === 'product' ? 'selected' : '' }}>Produk Jadi</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <select name="items[{{ $idx }}][item_id]" class="item-select w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm" required>
                                        <option value="">Pilih item</option>
                                        <optgroup label="Bahan Baku" class="raw-group {{ ($item['item_type'] ?? '') === 'product' ? 'hidden' : '' }}">
                                            @foreach($rawMaterials as $rm)<option value="{{ $rm->id }}" {{ ($item['item_id'] ?? '') == $rm->id ? 'selected' : '' }}>{{ $rm->name }}</option>@endforeach
                                        </optgroup>
                                        <optgroup label="Produk Jadi" class="prod-group {{ ($item['item_type'] ?? '') === 'product' ? '' : 'hidden' }}">
                                            @foreach($products as $p)<option value="{{ $p->id }}" {{ ($item['item_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="w-24">
                                    <input type="number" name="items[{{ $idx }}][quantity]" class="w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm text-center" min="0.01" step="0.01" placeholder="Qty" value="{{ $item['quantity'] ?? '' }}" required>
                                </div>
                                <button type="button" onclick="this.closest('.dist-item').remove()" class="px-2 py-2 text-destructive hover:text-destructive/80">×</button>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div>
                <x-input-label for="notes" value="Catatan (opsional)" />
                <textarea id="notes" name="notes" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" rows="2">{{ old('notes') }}</textarea>
            </div>

            <x-button class="w-full justify-center py-3 text-lg">Simpan Distribusi</x-button>
        </form>
    </x-card>
</div>

<div id="raw-options" class="hidden">@foreach($rawMaterials as $rm)<option value="{{ $rm->id }}">{{ $rm->name }}</option>@endforeach</div>
<div id="prod-options" class="hidden">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku ?? '-' }})</option>@endforeach</div>
@endsection
