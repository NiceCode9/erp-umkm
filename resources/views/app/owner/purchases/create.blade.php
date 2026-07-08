@extends('app.layouts.app')
@section('title', 'Pembelian Baru')
@section('content')
<div class="max-w-4xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Form Pembelian Bahan Baku</h2>
        <form action="{{ route('app.purchases.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang <span class="text-destructive">*</span></label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring" required>
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id')==$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<p class="text-sm text-destructive mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Supplier <span class="text-destructive">*</span></label>
                    <select name="supplier_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring" required>
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')<p class="text-sm text-destructive mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-input label="No. Invoice" name="invoice_no" value="{{ old('invoice_no') }}" required />
                </div>
                <div>
                    <x-input label="Tanggal" name="purchase_date" type="date" value="{{ old('purchase_date', date('Y-m-d')) }}" required />
                </div>
            </div>

            <h3 class="text-md font-semibold text-foreground mb-3 pb-2 border-b border-border">Item Pembelian</h3>

            <div x-data="{
                items: [{}],
                rawMaterials: {{ json_encode($rawMaterials->map(fn($rm) => ['id' => $rm->id, 'name' => $rm->name, 'base_unit' => $rm->base_unit])->values()) }},
                addItem() { this.items.push({}); },
                removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); },
                subtotal(index) {
                    const item = this.items[index];
                    const qty = parseFloat(item.quantity) || 0;
                    const price = parseFloat(item.unit_price) || 0;
                    return qty * price;
                },
                total() {
                    return this.items.reduce((sum, _, i) => sum + this.subtotal(i), 0);
                }
            }" class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-4 bg-muted rounded-[var(--radius)] border border-border">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <div>
                                <label class="text-xs text-muted-foreground">Bahan Baku</label>
                                <select :name="`items[${index}][raw_material_id]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                                    <option value="">-- Pilih --</option>
                                    <template x-for="rm in rawMaterials" :key="rm.id">
                                        <option :value="rm.id" x-text="rm.name + ' (' + rm.base_unit + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-muted-foreground">Qty</label>
                                <input type="number" step="0.01" min="0.01" :name="`items[${index}][quantity]`" x-model="item.quantity" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                            </div>
                            <div>
                                <label class="text-xs text-muted-foreground">Harga Satuan</label>
                                <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model="item.unit_price" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                            </div>
                            <div>
                                <label class="text-xs text-muted-foreground">Batch No.</label>
                                <input type="text" :name="`items[${index}][batch_no]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background" required>
                            </div>
                            <div>
                                <label class="text-xs text-muted-foreground">Expired (opsional)</label>
                                <input type="date" :name="`items[${index}][expired_date]`" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs text-muted-foreground">
                                Subtotal: <strong x-text="'Rp ' + subtotal(index).toLocaleString('id-ID')">0</strong>
                            </span>
                            <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-xs text-destructive hover:text-destructive/80">Hapus</button>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addItem()" class="text-sm text-secondary hover:text-secondary/80">+ Tambah Item</button>

                <div class="border-t border-border pt-3 flex justify-between items-center">
                    <strong class="text-foreground">Total:</strong>
                    <span class="text-xl font-bold text-primary" x-text="'Rp ' + total().toLocaleString('id-ID')">Rp 0</span>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <x-button type="submit">Simpan Pembelian</x-button>
                <a href="{{ route('app.purchases.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
            </div>
        </form>
    </x-card>
</div>
@endsection
