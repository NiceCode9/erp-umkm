@extends('app.layouts.app')
@section('title', 'Stok Opname')
@section('content')
<div class="max-w-3xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Stok Opname</h2>
        <form method="POST" x-data="{
            itemType: 'raw_material',
            itemId: '',
            branchId: '',
            batches: [],
            selectedBatch: null,
            systemQty: 0,
            actualQty: 0,
            async loadBatches() {
                if (!this.itemId || !this.branchId) { this.batches = []; return; }
                const resp = await fetch('{{ route('app.stock-opnames.batches') }}?item_type=' + this.itemType + '&item_id=' + this.itemId + '&branch_id=' + this.branchId);
                this.batches = await resp.json();
                this.selectedBatch = null;
                this.systemQty = 0;
                this.actualQty = 0;
            },
            selectBatch(id) {
                const b = this.batches.find(x => x.id == id);
                if (b) { this.systemQty = b.quantity_remaining; this.actualQty = b.quantity_remaining; }
            }
        }">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang</label>
                    <select name="branch_id" x-model="branchId" @change="loadBatches()" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" required>
                        <option value="">-- Pilih --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Tipe Item</label>
                    <select name="item_type" x-model="itemType" @change="loadBatches()" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background">
                        <option value="raw_material">Bahan Baku</option>
                        <option value="product">Produk Jadi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Item</label>
                    <select name="item_id" x-model="itemId" @change="loadBatches()" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" required>
                        <option value="">-- Pilih --</option>
                        <template x-if="itemType == 'raw_material'">
                            @foreach($rawMaterials as $rm)
                                <option value="{{ $rm->id }}">{{ $rm->name }}</option>
                            @endforeach
                        </template>
                        <template x-if="itemType == 'product'">
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </template>
                    </select>
                </div>

                <div x-show="batches.length > 0">
                    <label class="block text-sm font-medium text-foreground mb-1">Batch</label>
                    <select name="batch_id" x-model="selectedBatch" @change="selectBatch(selectedBatch)" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" required>
                        <option value="">-- Pilih Batch --</option>
                        <template x-for="b in batches" :key="b.id">
                            <option :value="b.id" x-text="b.label + ' (stok: ' + b.quantity_remaining + ')'"></option>
                        </template>
                    </select>
                </div>

                <div x-show="selectedBatch" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Stok Sistem</label>
                        <input type="number" name="system_quantity" x-model="systemQty" step="0.01" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Stok Aktual</label>
                        <input type="number" name="actual_quantity" x-model="actualQty" step="0.01" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" required>
                    </div>
                    <div class="col-span-2">
                        <span class="text-sm" x-show="systemQty != actualQty">
                            Selisih: <strong x-text="(actualQty - systemQty).toLocaleString('id-ID')" :class="actualQty >= systemQty ? 'text-primary' : 'text-destructive'"></strong>
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Alasan Opname <span class="text-destructive">*</span></label>
                    <textarea name="reason" rows="2" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" required></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Simpan Opname</x-button>
                    <a href="{{ route('app.stock-opnames.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
