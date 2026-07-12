@extends('app.layouts.app')
@section('title', 'Detail Pengiriman #' . $shipment->id)
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Detail Pengiriman</h1>
            <p class="text-muted-foreground">#{{ $shipment->id }} — {{ $shipment->type }}</p>
        </div>
        <a href="{{ route('app.owner.shipments.index') }}" class="px-4 py-2 border border-border rounded-[var(--radius)] hover:bg-muted transition text-sm">Kembali</a>
    </div>

    <x-card class="mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><p class="text-muted-foreground">Cabang</p><p class="font-semibold">{{ $shipment->branch->name ?? '-' }}</p></div>
            <div><p class="text-muted-foreground">Tipe</p><p><x-badge variant="{{ $shipment->type === 'borongan' ? 'info' : 'default' }}">{{ $shipment->type }}</x-badge></p></div>
            <div><p class="text-muted-foreground">Status</p><p><x-badge variant="{{ $shipment->status === 'delivered' ? 'success' : ($shipment->status === 'shipped' ? 'info' : 'warning') }}">{{ $shipment->status }}</x-badge></p></div>
            <div><p class="text-muted-foreground">Invoice Terkait</p><p class="font-semibold">{{ $shipment->sale?->invoice_no ?? '-' }}</p></div>
            <div class="col-span-2"><p class="text-muted-foreground">Tujuan</p><p class="font-semibold">{{ $shipment->destination }}</p></div>
            <div><p class="text-muted-foreground">Dibuat Oleh</p><p class="font-semibold">{{ $shipment->user->name ?? '-' }}</p></div>
            <div><p class="text-muted-foreground">Dibuat</p><p class="font-semibold">{{ $shipment->created_at->format('d/m/Y H:i') }}</p></div>
            @if($shipment->shipped_at)<div><p class="text-muted-foreground">Dikirim</p><p class="font-semibold">{{ $shipment->shipped_at->format('d/m/Y') }}</p></div>@endif
            @if($shipment->delivered_at)<div><p class="text-muted-foreground">Terkirim</p><p class="font-semibold">{{ $shipment->delivered_at->format('d/m/Y') }}</p></div>@endif
        </div>
    </x-card>

    <x-card class="mb-6">
        <h3 class="text-sm font-semibold text-foreground mb-3">Item Pengiriman</h3>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-border"><th class="text-left py-2 text-muted-foreground">Produk</th><th class="text-center py-2 text-muted-foreground">Qty</th></tr></thead>
            <tbody>
                @foreach($shipment->items as $item)
                    <tr class="border-b border-border/50">
                        <td class="py-2">{{ $item->product->name ?? '-' }}</td>
                        <td class="text-center py-2">{{ format_number($item->quantity) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    @if($shipment->status !== 'delivered')
        <div class="flex gap-3">
            @if($shipment->status === 'pending')
                <form method="POST" action="{{ route('app.owner.shipments.update-status', $shipment) }}">
                    @csrf
                    <input type="hidden" name="status" value="shipped">
                    <x-button variant="info">Tandai Dikirim</x-button>
                </form>
            @endif
            @if($shipment->status === 'shipped')
                <form method="POST" action="{{ route('app.owner.shipments.update-status', $shipment) }}">
                    @csrf
                    <input type="hidden" name="status" value="delivered">
                    <x-button variant="success">Tandai Terkirim</x-button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
