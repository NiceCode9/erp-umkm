@extends('app.layouts.app')
@section('title', 'Detail Pembelian #' . $purchase->invoice_no)
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-card>
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">Pembelian #{{ $purchase->invoice_no }}</h2>
                <p class="text-sm text-muted-foreground">{{ $purchase->purchase_date->format('d M Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-muted-foreground">Status Bayar</p>
                @switch($purchase->payment_status)
                    @case('paid')<x-badge variant="success">Lunas</x-badge>@break
                    @case('partial')<x-badge variant="warning">Sebagian</x-badge>@break
                    @default<x-badge variant="danger">Belum Dibayar</x-badge>
                @endswitch
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm mb-4 p-4 bg-muted rounded-[var(--radius)]">
            <div><span class="text-muted-foreground">Supplier:</span> <strong>{{ $purchase->supplier->name }}</strong></div>
            <div><span class="text-muted-foreground">Cabang:</span> <strong>{{ $purchase->branch->name }}</strong></div>
            <div><span class="text-muted-foreground">Total:</span> <strong>{{ format_currency($purchase->total_amount) }}</strong></div>
            <div><span class="text-muted-foreground">Sisa Utang:</span> <strong>{{ format_currency($purchase->remainingAmount()) }}</strong></div>
        </div>

        <h3 class="text-sm font-semibold text-foreground mb-2">Item Pembelian</h3>
        <table class="w-full text-sm mb-4">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-3 py-2 text-left text-xs text-muted-foreground">Bahan Baku</th>
                    <th class="px-3 py-2 text-right text-xs text-muted-foreground">Qty</th>
                    <th class="px-3 py-2 text-right text-xs text-muted-foreground">Harga</th>
                    <th class="px-3 py-2 text-right text-xs text-muted-foreground">Subtotal</th>
                    <th class="px-3 py-2 text-left text-xs text-muted-foreground">Batch</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach($purchase->items as $item)
                    <tr>
                        <td class="px-3 py-2">{{ $item->rawMaterial->name }}</td>
                        <td class="px-3 py-2 text-right">{{ $item->quantity }}</td>
                        <td class="px-3 py-2 text-right">{{ format_currency($item->unit_price) }}</td>
                        <td class="px-3 py-2 text-right">{{ format_currency($item->subtotal) }}</td>
                        <td class="px-3 py-2 text-xs">{{ $item->batch_no }}@if($item->expired_date)<br><span class="text-muted-foreground">Exp: {{ $item->expired_date->format('d M Y') }}</span>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($purchase->returns->count())
            <h3 class="text-sm font-semibold text-foreground mb-2 mt-4">Retur</h3>
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Tanggal</th>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Alasan</th>
                        <th class="px-3 py-2 text-right text-xs text-muted-foreground">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($purchase->returns as $ret)
                        <tr>
                            <td class="px-3 py-2">{{ $ret->return_date->format('d M Y') }}</td>
                            <td class="px-3 py-2">{{ $ret->reason ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">{{ format_currency($ret->items->sum('subtotal')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($purchase->payments->count())
            <h3 class="text-sm font-semibold text-foreground mb-2 mt-4">Riwayat Pembayaran</h3>
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Tanggal</th>
                        <th class="px-3 py-2 text-right text-xs text-muted-foreground">Jumlah</th>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Metode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($purchase->payments as $pm)
                        <tr>
                            <td class="px-3 py-2">{{ $pm->paid_at->format('d M Y') }}</td>
                            <td class="px-3 py-2 text-right">{{ format_currency($pm->amount) }}</td>
                            <td class="px-3 py-2 capitalize">{{ $pm->method }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="flex gap-3 mt-6 pt-4 border-t border-border">
            @if($purchase->payment_status !== 'paid')
                <a href="{{ route('app.purchases.pay', $purchase) }}"><x-button>Bayar Utang</x-button></a>
            @endif
            <a href="{{ route('app.purchases.return.form', $purchase) }}"><x-button variant="secondary">Retur</x-button></a>
            <a href="{{ route('app.purchases.index') }}"><x-button variant="secondary" type="button">Kembali</x-button></a>
        </div>
    </x-card>
</div>
@endsection
