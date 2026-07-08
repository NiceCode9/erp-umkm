@extends('app.layouts.app')
@section('title', 'Retur Pembelian - ' . $purchase->invoice_no)
@section('content')
<div class="max-w-4xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Form Retur Pembelian</h2>
        <p class="text-sm text-muted-foreground mb-4">Invoice: <strong>{{ $purchase->invoice_no }}</strong> — Supplier: {{ $purchase->supplier->name }}</p>

        <form action="{{ route('app.purchases.return.store', $purchase) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <x-input label="Tanggal Retur" name="return_date" type="date" value="{{ old('return_date', date('Y-m-d')) }}" required />
                <div></div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-foreground mb-1">Alasan Retur</label>
                    <textarea name="reason" rows="2" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring">{{ old('reason') }}</textarea>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-foreground mb-3 pb-2 border-b border-border">Item yang diretur</h3>
            <div class="space-y-2">
                @foreach($purchase->items as $item)
                    @php
                        $batches = \App\Models\RawMaterialBatch::where('raw_material_id', $item->raw_material_id)
                            ->where('branch_id', $purchase->branch_id)
                            ->where('quantity_remaining', '>', 0)
                            ->orderBy('expired_date')
                            ->get();
                    @endphp
                    @if($batches->count())
                        <div class="p-3 bg-muted rounded-[var(--radius)] border border-border">
                            <p class="text-sm font-medium text-foreground mb-2">{{ $item->rawMaterial->name }} ({{ $item->quantity }} {{ $item->rawMaterial->base_unit }})</p>
                            @foreach($batches as $batch)
                                <div class="flex gap-3 items-center text-sm">
                                    <input type="hidden" name="items[{{ $loop->parent->index }}][purchase_item_id]" value="{{ $item->id }}">
                                    <input type="hidden" name="items[{{ $loop->parent->index }}][raw_material_batch_id]" value="{{ $batch->id }}">
                                    <span class="text-muted-foreground w-40">Batch: {{ $batch->batch_no }} (sisa {{ $batch->quantity_remaining }})</span>
                                    <input type="number" name="items[{{ $loop->parent->index }}][quantity]" placeholder="Qty" min="0" max="{{ $batch->quantity_remaining }}" step="0.01" class="w-24 border border-input rounded-[var(--radius)] px-2 py-1 text-sm bg-background">
                                    <input type="number" name="items[{{ $loop->parent->index }}][unit_price]" placeholder="Harga" value="{{ $item->unit_price }}" step="0.01" class="w-28 border border-input rounded-[var(--radius)] px-2 py-1 text-sm bg-background">
                                    <span class="text-muted-foreground">Exp: {{ $batch->expired_date?->format('d M Y') ?? '-' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="flex gap-3 mt-6">
                <x-button type="submit">Simpan Retur</x-button>
                <a href="{{ route('app.purchases.show', $purchase) }}"><x-button variant="secondary" type="button">Batal</x-button></a>
            </div>
        </form>
    </x-card>
</div>
@endsection
