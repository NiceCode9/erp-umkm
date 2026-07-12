@extends('app.layouts.app')
@section('title', 'Retur Penjualan')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <x-card>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-foreground mb-1">Retur Penjualan</h2>
            <p class="text-muted-foreground">Invoice: {{ $sale->invoice_no }} | {{ $sale->customer_name ?? '-' }} | {{ $sale->created_at->format('d/m/Y') }}</p>
        </div>

        <form method="POST" action="{{ route('app.owner.sales.return.store', $sale) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="return_date" value="Tanggal Retur" />
                    <x-input id="return_date" name="return_date" type="date" class="mt-1 block w-full" value="{{ old('return_date', date('Y-m-d')) }}" required />
                    <x-input-error :messages="$errors->get('return_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="reason" value="Alasan Retur" />
                    <input id="reason" name="reason" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" placeholder="Opsional" value="{{ old('reason') }}" />
                    <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium text-foreground">Item yang Dikembalikan</p>
                @foreach($sale->items as $item)
                    @php
                        $alreadyReturned = (float) $sale->returns->flatMap->items->where('sale_item_id', $item->id)->sum('quantity');
                        $maxReturn = (float) $item->quantity - $alreadyReturned;
                    @endphp
                    @if($maxReturn > 0)
                        <label class="flex items-center gap-3 p-3 bg-muted rounded-[var(--radius)] cursor-pointer hover:bg-muted/80 transition">
                            <input type="hidden" name="items[{{ $item->id }}][sale_item_id]" value="{{ $item->id }}">
                            <input type="checkbox" class="return-checkbox rounded border-border text-primary focus:ring-ring" data-id="{{ $item->id }}">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-foreground text-sm">{{ $item->product->name }}</p>
                                <p class="text-xs text-muted-foreground">Terjual: {{ format_number($item->quantity) }} × {{ format_currency($item->unit_price) }}</p>
                            </div>
                            <div class="w-24">
                                <input type="number" name="items[{{ $item->id }}][quantity]" class="return-qty w-full border border-input rounded-[var(--radius)] px-2 py-1 text-sm text-center" min="0.01" max="{{ $maxReturn }}" step="0.01" placeholder="0" disabled data-max="{{ $maxReturn }}">
                                <p class="text-xs text-muted-foreground text-center mt-0.5">Max: {{ format_number($maxReturn) }}</p>
                            </div>
                        </label>
                    @endif
                @endforeach
            </div>

            <x-button type="submit" class="w-full justify-center py-3 text-lg">Simpan Retur</x-button>
        </form>
    </x-card>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.return-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const qtyInput = this.closest('label').querySelector('.return-qty');
            qtyInput.disabled = !this.checked;
            if (!this.checked) qtyInput.value = '';
        });
    });
</script>
@endpush
@endsection
