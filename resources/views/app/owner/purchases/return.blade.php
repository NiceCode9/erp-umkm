@extends('app.layouts.app')
@section('title', 'Retur Pembelian - ' . $purchase->invoice_no)
@section('content')
<div class="max-w-4xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Form Retur Pembelian</h2>
        <p class="text-sm text-muted-foreground mb-4">
            Invoice: <strong>{{ $purchase->invoice_no }}</strong> — Supplier: {{ $purchase->supplier->name }}
        </p>

        @if($returnable->isEmpty())
            <div class="p-4 bg-warning/10 text-warning rounded-[var(--radius)] border border-warning/20 mb-4">
                <p class="text-sm">
                    Tidak ada item yang dapat diretur. Semua item sudah diretur penuh,
                    atau stok batch-nya sudah habis.
                </p>
            </div>
        @endif

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
                {{--
                    PENTING: index item memakai counter GLOBAL (bukan $loop->index per
                    batch). Versi lama memakai $loop->parent->index sehingga beberapa
                    batch dari item yang sama menghasilkan name field yang sama
                    (items[0][quantity] berulang) — PHP hanya menyimpan nilai
                    terakhir, jadi Owner diam-diam hanya bisa retur satu batch.
                --}}
                @php $rowIndex = 0; @endphp

                @foreach($returnable as $entry)
                    @php
                        $item = $entry['item'];
                        $batches = $entry['batches'];
                        $returnableQty = $item->returnableQuantity();
                        $alreadyReturned = $item->returnedQuantity();
                    @endphp

                    <div class="p-3 bg-muted rounded-[var(--radius)] border border-border">
                        <p class="text-sm font-medium text-foreground mb-1">
                            {{ $item->rawMaterial->name }}
                            <span class="text-muted-foreground">
                                — dibeli {{ $item->quantity }} {{ $item->rawMaterial->base_unit }}
                                @if($alreadyReturned > 0)
                                    , sudah diretur {{ $alreadyReturned }}
                                @endif
                                , sisa dapat diretur <strong>{{ $returnableQty }}</strong>
                            </span>
                        </p>

                        @foreach($batches as $batch)
                            <div class="flex gap-3 items-center text-sm mt-2">
                                <input type="hidden" name="items[{{ $rowIndex }}][purchase_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $rowIndex }}][raw_material_batch_id]" value="{{ $batch->id }}">
                                <span class="text-muted-foreground w-56">
                                    Batch: {{ $batch->batch_no }} (sisa {{ $batch->quantity_remaining }})
                                </span>
                                <input type="number" name="items[{{ $rowIndex }}][quantity]" placeholder="Qty"
                                    min="0" max="{{ min((float) $batch->quantity_remaining, $returnableQty) }}" step="0.01"
                                    class="w-24 border border-input rounded-[var(--radius)] px-2 py-1 text-sm bg-background">
                                {{-- Harga TIDAK bisa diedit: selalu diambil dari PurchaseItem di server. --}}
                                <span class="text-muted-foreground w-32">
                                    Harga: {{ format_currency($item->unit_price) }}
                                </span>
                                <span class="text-muted-foreground">
                                    Exp: {{ $batch->expired_date?->format('d M Y') ?? '-' }}
                                </span>
                            </div>
                            @php $rowIndex++; @endphp
                        @endforeach
                    </div>
                @endforeach
            </div>

            @error('items')
                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
            @enderror
            @error('items.*.*')
                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
            @enderror

            <div class="flex gap-3 mt-6">
                <x-button type="submit" :disabled="$returnable->isEmpty()">Simpan Retur</x-button>
                <a href="{{ route('app.purchases.show', $purchase) }}"><x-button variant="secondary" type="button">Batal</x-button></a>
            </div>
        </form>
    </x-card>
</div>
@endsection