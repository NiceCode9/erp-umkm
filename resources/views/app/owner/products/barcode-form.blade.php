@extends('app.layouts.app')

@section('title', 'Cetak Barcode')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8">
    <x-card>
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-foreground mb-2">Cetak Barcode</h2>
            <p class="text-muted-foreground">Generate label barcode untuk ditempel ke packaging</p>
        </div>

        <div class="p-4 bg-muted rounded-[var(--radius)] mb-6 space-y-1">
            <p><span class="text-muted-foreground">Produk:</span> <span class="font-semibold">{{ $product->name }}</span></p>
            <p><span class="text-muted-foreground">SKU:</span> <span class="font-semibold">{{ $product->sku ?? 'SKU-' . $product->id }}</span></p>
        </div>

        <form method="GET" action="{{ route('app.products.print-barcode', $product) }}" target="_blank" class="space-y-4">
            <div>
                <x-input-label for="qty" value="Jumlah Label" />
                <x-input
                    id="qty"
                    name="qty"
                    type="number"
                    class="mt-1 block w-full text-lg py-3"
                    value="10"
                    min="1"
                    max="100"
                    required
                />
                <p class="text-xs text-muted-foreground mt-1">Maksimal 100 label per cetakan</p>
            </div>

            <x-button class="w-full justify-center py-3 text-lg">
                Cetak Barcode
            </x-button>
        </form>
    </x-card>
</div>
@endsection
