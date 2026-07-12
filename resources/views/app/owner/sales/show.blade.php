@extends('app.layouts.app')

@section('title', 'Detail Penjualan #' . $sale->invoice_no)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Detail Penjualan</h1>
            <p class="text-muted-foreground">{{ $sale->invoice_no }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.owner.sales.return.create', $sale) }}" class="px-4 py-2 bg-warning text-warning-foreground rounded-[var(--radius)] font-semibold hover:bg-warning/90 transition text-sm">Retur</a>
            <a href="{{ route('app.owner.sales.index') }}" class="px-4 py-2 border border-border rounded-[var(--radius)] hover:bg-muted transition text-sm">Kembali</a>
        </div>
    </div>

    <x-card class="mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-muted-foreground">Tanggal</p>
                <p class="font-semibold">{{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : '-' }}</p>
            </div>
            <div>
                <p class="text-muted-foreground">Cabang</p>
                <p class="font-semibold">{{ $sale->branch->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-muted-foreground">Kasir</p>
                <p class="font-semibold">{{ $sale->user->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-muted-foreground">Pelanggan</p>
                <p class="font-semibold">{{ $sale->customer_name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-muted-foreground">Shift Kasir</p>
                <p class="font-semibold">{{ $sale->cashierShift?->opened_at?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-muted-foreground">Metode Bayar</p>
                <p class="font-semibold capitalize">{{ $sale->payment_method ?? '-' }}</p>
            </div>
        </div>
    </x-card>

    <x-card class="mb-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border">
                    <th class="text-left py-2 text-muted-foreground">Produk</th>
                    <th class="text-center py-2 text-muted-foreground">Qty</th>
                    <th class="text-right py-2 text-muted-foreground">Harga</th>
                    <th class="text-right py-2 text-muted-foreground">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr class="border-b border-border/50">
                        <td class="py-2">{{ $item->product->name }}
                            @if($item->productUnit)
                                <span class="text-xs text-muted-foreground">({{ $item->productUnit->unit_name }})</span>
                            @endif
                        </td>
                        <td class="text-center py-2">{{ format_number($item->quantity) }}</td>
                        <td class="text-right py-2">{{ format_currency($item->unit_price) }}</td>
                        <td class="text-right py-2 font-medium">{{ format_currency($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right py-2 text-muted-foreground">Subtotal</td>
                    <td class="text-right py-2 font-semibold">{{ format_currency($sale->subtotal) }}</td>
                </tr>
                @if($sale->discount_amount > 0)
                    <tr>
                        <td colspan="3" class="text-right py-2 text-warning">Diskon</td>
                        <td class="text-right py-2 text-warning">-{{ format_currency($sale->discount_amount) }}</td>
                    </tr>
                @endif
                @if($sale->tax_amount > 0)
                    <tr>
                        <td colspan="3" class="text-right py-2">Pajak ({{ format_number($sale->tax_percentage_applied) }}%)</td>
                        <td class="text-right py-2">{{ format_currency($sale->tax_amount) }}</td>
                    </tr>
                @endif
                <tr class="border-t border-border">
                    <td colspan="3" class="text-right py-2 font-bold text-lg">Total</td>
                    <td class="text-right py-2 font-bold text-lg text-primary">{{ format_currency($sale->total_amount) }}</td>
                </tr>
            </tfoot>
        </table>
    </x-card>

    @if($sale->items->filter(fn($i) => $i->batches->count())->count())
        <x-card>
            <h3 class="text-lg font-semibold mb-3">Rincian Batch (FEFO)</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th class="text-left py-2 text-muted-foreground">Item</th>
                        <th class="text-center py-2 text-muted-foreground">Batch</th>
                        <th class="text-center py-2 text-muted-foreground">Expired</th>
                        <th class="text-right py-2 text-muted-foreground">Qty Diambil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        @foreach($item->batches as $sib)
                            <tr class="border-b border-border/50">
                                <td class="py-2">{{ $item->product->name }}</td>
                                <td class="text-center py-2 text-xs">{{ $sib->productBatch?->batch_no ?? '-' }}</td>
                                <td class="text-center py-2 text-xs">{{ $sib->productBatch?->expired_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="text-right py-2">{{ format_number($sib->quantity) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif
</div>
@endsection
