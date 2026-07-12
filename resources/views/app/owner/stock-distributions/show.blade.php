@extends('app.layouts.app')
@section('title', 'Detail Distribusi')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Detail Distribusi Stok</h1>
            <p class="text-muted-foreground">#{{ $stock_distribution->id }}</p>
        </div>
        <a href="{{ route('app.owner.stock-distributions.index') }}" class="px-4 py-2 border border-border rounded-[var(--radius)] hover:bg-muted transition text-sm">Kembali</a>
    </div>

    <x-card class="mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><p class="text-muted-foreground">Cabang Asal</p><p class="font-semibold">{{ $stock_distribution->originBranch->name ?? '-' }}</p></div>
            <div><p class="text-muted-foreground">Cabang Tujuan</p><p class="font-semibold">{{ $stock_distribution->destinationBranch->name ?? '-' }}</p></div>
            <div><p class="text-muted-foreground">Status</p><p><x-badge variant="{{ $stock_distribution->status === 'received' ? 'success' : ($stock_distribution->status === 'shipped' ? 'info' : 'warning') }}">{{ $stock_distribution->status }}</x-badge></p></div>
            <div><p class="text-muted-foreground">Dibuat Oleh</p><p class="font-semibold">{{ $stock_distribution->user->name ?? '-' }}</p></div>
            @if($stock_distribution->shipped_at)<div><p class="text-muted-foreground">Dikirim</p><p class="font-semibold">{{ $stock_distribution->shipped_at->format('d/m/Y H:i') }}</p></div>@endif
            @if($stock_distribution->received_at)<div><p class="text-muted-foreground">Diterima</p><p class="font-semibold">{{ $stock_distribution->received_at->format('d/m/Y H:i') }}</p></div>@endif
            @if($stock_distribution->notes)
                <div class="col-span-2"><p class="text-muted-foreground">Catatan</p><p class="font-semibold">{{ $stock_distribution->notes }}</p></div>
            @endif
        </div>
    </x-card>

    <x-card class="mb-6">
        <h3 class="text-sm font-semibold text-foreground mb-3">Item</h3>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-border"><th class="text-left py-2 text-muted-foreground">Tipe</th><th class="text-left py-2 text-muted-foreground">Nama</th><th class="text-right py-2 text-muted-foreground">Qty</th></tr></thead>
            <tbody>
                @foreach($stock_distribution->items as $item)
                    <tr class="border-b border-border/50">
                        <td class="py-2"><x-badge variant="{{ $item->item_type === 'raw_material' ? 'default' : 'info' }}">{{ $item->item_type === 'raw_material' ? 'Bahan Baku' : 'Produk' }}</x-badge></td>
                        <td class="py-2">{{ $item->rawMaterial?->name ?? $item->product?->name ?? '-' }}</td>
                        <td class="py-2 text-right font-medium">{{ format_number($item->quantity) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    @if($stock_distribution->items->filter(fn($i) => $i->batchRecords->count())->count())
        <x-card class="mb-6">
            <h3 class="text-sm font-semibold text-foreground mb-3">Rincian Batch (FEFO)</h3>
            <table class="w-full text-sm">
                <thead><tr class="border-b border-border"><th class="text-left py-2 text-muted-foreground">Item</th><th class="text-center py-2 text-muted-foreground">Batch</th><th class="text-center py-2 text-muted-foreground">Expired</th><th class="text-right py-2 text-muted-foreground">Qty</th></tr></thead>
                <tbody>
                    @foreach($stock_distribution->items as $item)
                        @foreach($item->batchRecords as $br)
                            <tr class="border-b border-border/50">
                                <td class="py-2">{{ $item->rawMaterial?->name ?? $item->product?->name ?? '-' }}</td>
                                <td class="py-2 text-center text-xs">
                                    @if($item->item_type === 'raw_material')
                                        {{ $br->rawMaterialBatch?->batch_no ?? '-' }}
                                    @else
                                        {{ $br->productBatch?->batch_no ?? '-' }}
                                    @endif
                                </td>
                                <td class="py-2 text-center text-xs">
                                    @if($item->item_type === 'raw_material')
                                        {{ $br->rawMaterialBatch?->expired_date?->format('d/m/Y') ?? '-' }}
                                    @else
                                        {{ $br->productBatch?->expired_date?->format('d/m/Y') ?? '-' }}
                                    @endif
                                </td>
                                <td class="py-2 text-right">{{ format_number($br->quantity) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif

    @if($stock_distribution->status === 'pending')
        <form method="POST" action="{{ route('app.owner.stock-distributions.ship', $stock_distribution) }}" onsubmit="return confirm('Kirim distribusi ini? Stok akan berkurang dari cabang asal.')">
            @csrf
            <x-button variant="info" class="w-full justify-center py-3 text-lg">Kirim Distribusi</x-button>
        </form>
    @endif

    @if($stock_distribution->status === 'shipped')
        <form method="POST" action="{{ route('app.owner.stock-distributions.receive', $stock_distribution) }}" onsubmit="return confirm('Konfirmasi penerimaan? Stok akan masuk ke cabang tujuan.')">
            @csrf
            <x-button variant="success" class="w-full justify-center py-3 text-lg">Konfirmasi Terima</x-button>
        </form>
    @endif
</div>
@endsection
