@extends('app.layouts.app')
@section('title', 'Buat Pengiriman')
@push('scripts')
<script>
    function toggleSaleFields() {
        const saleId = document.getElementById('sale_id').value;
        const saleItemsSection = document.getElementById('sale-items-section');
        const manualItemsSection = document.getElementById('manual-items-section');

        saleItemsSection.classList.toggle('hidden', !saleId);
        manualItemsSection.classList.toggle('hidden', !!saleId);

        if (saleId) {
            loadSaleItems(saleId);
        }
    }

    function loadSaleItems(saleId) {
        fetch(`/app/sale-items-for-shipment/${saleId}`)
            .then(r => r.json())
            .then(items => {
                const container = document.getElementById('sale-items-list');
                container.innerHTML = '';
                items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-3 p-3 bg-muted rounded-[var(--radius)]';
                    div.innerHTML = `
                        <input type="hidden" name="items[${item.sale_item_id}][product_id]" value="${item.product_id}">
                        <input type="hidden" name="items[${item.sale_item_id}][sale_item_id]" value="${item.sale_item_id}">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-foreground text-sm">${item.product_name}</p>
                            <p class="text-xs text-muted-foreground">Terjual: ${item.quantity_sold} × ${formatRupiah(item.unit_price)}</p>
                        </div>
                        <div class="w-24">
                            <input type="number" name="items[${item.sale_item_id}][quantity]" class="w-full border border-input rounded-[var(--radius)] px-2 py-1 text-sm text-center" min="0.01" max="${item.quantity_sold}" step="0.01" required>
                        </div>
                    `;
                    container.appendChild(div);
                });
            });
    }

    function formatRupiah(v) { return 'Rp ' + Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
</script>
@endpush
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <x-card>
        <h2 class="text-2xl font-bold text-foreground mb-6">Buat Pengiriman Baru</h2>

        <form method="POST" action="{{ route('app.owner.shipments.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="branch_id" value="Cabang Asal" />
                    <select id="branch_id" name="branch_id" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" required>
                        <option value="">Pilih Cabang</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('branch_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="type" value="Tipe Pengiriman" />
                    <select id="type" name="type" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" required>
                        <option value="ecer" {{ old('type') === 'ecer' ? 'selected' : '' }}>Ecer</option>
                        <option value="borongan" {{ old('type') === 'borongan' ? 'selected' : '' }}>Borongan</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="sale_id" value="Referensi Penjualan (opsional)" />
                <select id="sale_id" name="sale_id" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" onchange="toggleSaleFields()">
                    <option value="">— Pengiriman Mandiri (tanpa referensi penjualan) —</option>
                    @foreach($sales as $s)
                        <option value="{{ $s->id }}" {{ old('sale_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->invoice_no }} — {{ $s->customer_name ?? 'Walk-in' }} — {{ $s->branch->name ?? '' }} ({{ $s->created_at->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('sale_id')" class="mt-1" />
                <p class="text-xs text-muted-foreground mt-1">Pilih penjualan untuk auto-populate item. Kosongkan untuk input manual.</p>
            </div>

            <div id="sale-items-section" class="{{ old('sale_id') ? '' : 'hidden' }} space-y-2">
                <p class="text-sm font-medium text-foreground">Item dari Penjualan</p>
                <div id="sale-items-list" class="space-y-2"></div>
            </div>

            <div id="manual-items-section" class="{{ old('sale_id') ? 'hidden' : '' }} space-y-2">
                <p class="text-sm font-medium text-foreground">Item Pengiriman (input manual)</p>
                <div id="manual-items-list">
                    <div class="flex gap-2 items-start manual-item">
                        <div class="flex-1">
                            <select name="items[0][product_id]" class="w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:border-transparent" required>
                                <option value="">Pilih Produk</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-24">
                            <input type="number" name="items[0][quantity]" class="w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm text-center" min="0.01" step="0.01" placeholder="Qty" required>
                        </div>
                        <input type="hidden" name="items[0][sale_item_id]" value="">
                        <button type="button" onclick="this.closest('.manual-item').remove()" class="px-2 py-2 text-destructive hover:text-destructive/80">×</button>
                    </div>
                </div>
                <button type="button" onclick="addManualItem()" class="text-sm text-secondary hover:text-secondary/80">+ Tambah Item</button>
            </div>

            <div>
                <x-input-label for="destination" value="Tujuan Pengiriman" />
                <textarea id="destination" name="destination" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" rows="2" required>{{ old('destination') }}</textarea>
                <x-input-error :messages="$errors->get('destination')" class="mt-1" />
            </div>

            <x-button class="w-full justify-center py-3 text-lg">Simpan Pengiriman</x-button>
        </form>
    </x-card>
</div>
<script>
    let manualIdx = 1;
    function addManualItem() {
        const container = document.getElementById('manual-items-list');
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-start manual-item';
        div.innerHTML = `
            <div class="flex-1">
                <select name="items[${manualIdx}][product_id]" class="w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:border-transparent" required>
                    <option value="">Pilih Produk</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="w-24">
                <input type="number" name="items[${manualIdx}][quantity]" class="w-full border border-input rounded-[var(--radius)] px-2 py-2 text-sm text-center" min="0.01" step="0.01" placeholder="Qty" required>
            </div>
            <input type="hidden" name="items[${manualIdx}][sale_item_id]" value="">
            <button type="button" onclick="this.closest('.manual-item').remove()" class="px-2 py-2 text-destructive hover:text-destructive/80">×</button>
        `;
        container.appendChild(div);
        manualIdx++;
    }

    if (document.getElementById('sale_id').value) {
        toggleSaleFields();
    }
</script>
@endsection
