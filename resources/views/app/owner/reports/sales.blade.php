@extends('app.layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Laporan Penjualan</h1>
            <p class="text-muted-foreground">Ringkasan dan detail transaksi penjualan</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.owner.reports.export', array_merge(['sales', 'excel'], request()->query())) }}">
                <x-button variant="secondary" size="sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </x-button>
            </a>
            <a href="{{ route('app.owner.reports.export', array_merge(['sales', 'pdf'], request()->query())) }}">
                <x-button variant="danger" size="sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF
                </x-button>
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('app.owner.reports.sales') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang</label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent text-sm">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" size="sm">Filter</x-button>
                    <a href="{{ route('app.owner.reports.sales') }}"><x-button variant="secondary" size="sm" type="button">Reset</x-button></a>
                </div>
            </div>
        </form>
    </x-card>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-card>
            <p class="text-sm text-muted-foreground">Total Penjualan</p>
            <p class="text-2xl font-bold text-foreground mt-1">{{ format_currency($summary['total_sales'] ?? 0) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-muted-foreground">Jumlah Transaksi</p>
            <p class="text-2xl font-bold text-foreground mt-1">{{ format_number($summary['total_transactions'] ?? 0) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-muted-foreground">Rata-rata per Transaksi</p>
            <p class="text-2xl font-bold text-foreground mt-1">{{ format_currency($summary['average_per_transaction'] ?? 0) }}</p>
        </x-card>
    </div>

    {{-- Top Products --}}
    @if(isset($topProducts) && count($topProducts))
        <x-card class="mb-6">
            <h3 class="text-lg font-semibold text-foreground mb-4">Produk Terlaris</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Produk</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Qty Terjual</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Total Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($topProducts as $index => $product)
                            <tr class="hover:bg-muted/50">
                                <td class="px-4 py-3 text-muted-foreground">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium">{{ $product['name'] }}</td>
                                <td class="px-4 py-3 text-right">{{ format_number($product['total_qty'] ?? 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ format_currency($product['total_revenue'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif

    {{-- Detail Table --}}
    <x-data-table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Invoice</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tanggal</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Kasir</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Subtotal</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Diskon</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Pajak</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Total</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
            </tr>
        </x-slot:header>
        <tbody>
            @forelse($sales as $sale)
                <tr class="hover:bg-muted/50 transition">
                    <td class="px-4 py-3 font-medium text-foreground">{{ $sale->invoice_no }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $sale->user->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ format_currency($sale->subtotal) }}</td>
                    <td class="px-4 py-3 text-right">{{ format_currency($sale->discount_amount) }}</td>
                    <td class="px-4 py-3 text-right">{{ format_currency($sale->tax_amount) }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ format_currency($sale->total_amount) }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge :variant="$sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger')">
                            {{ $sale->payment_status }}
                        </x-badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">Tidak ada data penjualan untuk filter yang dipilih</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-4">
        {{ $sales->links() }}
    </div>
</div>
@endsection
