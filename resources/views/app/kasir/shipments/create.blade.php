@extends('app.layouts.app')
@section('title', 'Buat Pengiriman')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <x-card>
        <h2 class="text-2xl font-bold text-foreground mb-1">Buat Pengiriman</h2>
        <p class="text-muted-foreground mb-6">Pengiriman untuk transaksi {{ $sale->invoice_no }}</p>

        <form method="POST" action="{{ route('app.kasir.shipments.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="type" value="Tipe Pengiriman" />
                    <select id="type" name="type" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" required>
                        <option value="ecer" {{ old('type') === 'ecer' ? 'selected' : '' }}>Ecer</option>
                        <option value="borongan" {{ old('type') === 'borongan' ? 'selected' : '' }}>Borongan</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="recipient_name" value="Nama Penerima" />
                    <x-input id="recipient_name" name="recipient_name" type="text" class="mt-1 block w-full"
                        value="{{ old('recipient_name', $sale->customer_name ?? '') }}" required />
                    <x-input-error :messages="$errors->get('recipient_name')" class="mt-1" />
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium text-foreground">Item Pengiriman (dari transaksi {{ $sale->invoice_no }})</p>
                @foreach($sale->items as $item)
                    <div class="flex items-center gap-3 p-3 bg-muted rounded-[var(--radius)]">
                        <input type="hidden" name="items[{{ $item->id }}][product_id]" value="{{ $item->product_id }}">
                        <input type="hidden" name="items[{{ $item->id }}][sale_item_id]" value="{{ $item->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-foreground text-sm">{{ $item->product->name }}</p>
                            <p class="text-xs text-muted-foreground">Terjual: {{ format_number($item->quantity) }} × {{ format_currency($item->unit_price) }}</p>
                        </div>
                        <div class="w-24">
                            <input type="number" name="items[{{ $item->id }}][quantity]" class="w-full border border-input rounded-[var(--radius)] px-2 py-1 text-sm text-center" min="0.01" max="{{ (float) $item->quantity }}" step="0.01" placeholder="Qty" required>
                        </div>
                    </div>
                @endforeach
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
@endsection
