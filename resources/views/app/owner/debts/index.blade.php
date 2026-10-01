@extends('app.layouts.app')
@section('title', 'Utang ke Supplier')
@section('content')

{{-- Ringkasan --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <x-card class="border border-warning/30">
        <p class="text-xs text-muted-foreground">Total Utang Outstanding</p>
        <p class="text-2xl font-semibold text-warning mt-1">{{ format_currency($payableTotal) }}</p>
        <p class="text-xs text-muted-foreground mt-1">{{ $payables->count() }} transaksi belum lunas</p>
    </x-card>
    <x-card class="border border-destructive/30">
        <p class="text-xs text-muted-foreground">Total Kredit ke Supplier</p>
        <p class="text-2xl font-semibold text-destructive mt-1">{{ format_currency($creditTotal) }}</p>
        <p class="text-xs text-muted-foreground mt-1">{{ $credits->count() }} transaksi retur melebihi sisa utang</p>
    </x-card>
</div>

{{-- Section 1: Utang outstanding --}}
<x-card class="mb-6">
    <h2 class="text-lg font-semibold mb-1">Utang Outstanding</h2>
    <p class="text-xs text-muted-foreground mb-4">
        Urut dari yang paling lama belum dibayar. Formula:
        <code>total − SUM(pembayaran) − SUM(retur)</code>.
    </p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Invoice</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Supplier</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Total</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Dibayar</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Retur</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Sisa Utang</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($payables as $d)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $d->invoice_no }}</td>
                        <td class="px-4 py-3">{{ $d->supplier->name }}</td>
                        <td class="px-4 py-3">{{ $d->branch->name }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->total_amount) }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->paid_amount) }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->returned_amount) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ format_currency($d->outstanding_amount) }}</td>
                        <td class="px-4 py-3">
                            @if($d->payment_status === 'partial')
                                <x-badge variant="warning">Sebagian</x-badge>
                            @else
                                <x-badge variant="danger">Belum</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                @can('pay', $d)
                                    <a href="{{ route('app.purchases.pay', $d) }}"><x-button size="sm">Bayar</x-button></a>
                                @endcan
                                <a href="{{ route('app.purchases.show', $d) }}"><x-button variant="secondary" size="sm">Detail</x-button></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-muted-foreground">Semua utang supplier sudah lunas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($purchases->hasPages())<div class="mt-4">{{ $purchases->links() }}</div>@endif
</x-card>

{{-- Section 2: Kredit ke supplier (retur melebihi sisa utang) --}}
<x-card>
    <h2 class="text-lg font-semibold mb-1">Kredit / Klaim ke Supplier</h2>
    <p class="text-xs text-muted-foreground mb-4">
        Retur pembelian melebihi sisa utang, sehingga supplier owes kita. Nilai ini muncul sebagai
        <code>outstanding</code> negatif dan TIDAK ikut di halaman utang di atas.
    </p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Invoice</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Supplier</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Total</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Dibayar</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Retur</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Kredit</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($credits as $d)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $d->invoice_no }}</td>
                        <td class="px-4 py-3">{{ $d->supplier->name }}</td>
                        <td class="px-4 py-3">{{ $d->branch->name }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->total_amount) }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->paid_amount) }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->returned_amount) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-destructive">{{ format_currency(abs($d->outstanding_amount)) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('app.purchases.show', $d) }}"><x-button variant="secondary" size="sm">Detail</x-button></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Tidak ada kredit ke supplier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection