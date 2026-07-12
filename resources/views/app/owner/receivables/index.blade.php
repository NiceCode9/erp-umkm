@extends('app.layouts.app')
@section('title', 'Piutang')
@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Piutang dari Pembeli</h1>
            <p class="text-muted-foreground">Transaksi yang belum lunas</p>
        </div>
    </div>

    <x-data-table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Invoice</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tanggal</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Cabang</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Pelanggan</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Total</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Dibayar</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Retur</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Sisa</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Aksi</th>
            </tr>
        </x-slot:header>
        <tbody>
            @forelse($sales as $sale)
                <tr class="hover:bg-muted/50 transition">
                    <td class="px-4 py-3 font-medium text-foreground">{{ $sale->invoice_no }}</td>
                    <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">{{ $sale->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $sale->branch->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $sale->customer_name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ format_currency($sale->total_amount) }}</td>
                    <td class="px-4 py-3 text-right">{{ $sale->total_paid > 0 ? format_currency($sale->total_paid) : '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ $sale->total_returned > 0 ? format_currency($sale->total_returned) : '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $sale->outstanding > 0 ? 'text-destructive' : '' }}">{{ format_currency($sale->outstanding) }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge variant="{{ $sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger') }}">
                            {{ $sale->payment_status }}
                        </x-badge>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('app.owner.receivables.pay', $sale) }}" class="text-secondary hover:text-secondary/80 text-sm">Bayar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-12 text-center text-muted-foreground">Semua transaksi sudah lunas</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>
</div>
@endsection
