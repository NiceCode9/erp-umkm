@extends('app.layouts.app')

@section('title', 'Laporan Utang & Piutang')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Laporan Utang & Piutang</h1>
            <p class="text-muted-foreground">Rekap utang ke supplier dan piutang dari pembeli</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.owner.reports.export', array_merge(['supplier-debts', 'excel'], request()->query())) }}">
                <x-button variant="secondary" size="sm">Excel</x-button>
            </a>
            <a href="{{ route('app.owner.reports.export', array_merge(['supplier-debts', 'pdf'], request()->query())) }}">
                <x-button variant="danger" size="sm">PDF</x-button>
            </a>
        </div>
    </div>

    <div class="mb-8">
        <h2 class="text-lg font-semibold text-foreground mb-4">Utang ke Supplier</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <x-card>
                <p class="text-sm text-muted-foreground">Total Sisa Utang</p>
                <p class="text-2xl font-bold text-destructive mt-1">{{ format_currency($supplierDebts->sum('outstanding')) }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-muted-foreground">Jumlah Transaksi</p>
                <p class="text-2xl font-bold text-foreground mt-1">{{ $supplierDebts->count() }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-muted-foreground">Rata-rata per Transaksi</p>
                <p class="text-2xl font-bold text-foreground mt-1">{{ $supplierDebts->count() > 0 ? format_currency($supplierDebts->sum('outstanding') / $supplierDebts->count()) : format_currency(0) }}</p>
            </x-card>
        </div>

        <x-data-table>
            <x-slot:header>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Supplier</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Invoice</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Sisa Utang</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                </tr>
            </x-slot:header>
            <tbody>
                @forelse($supplierDebts as $debt)
                    <tr class="hover:bg-muted/50 transition">
                        <td class="px-4 py-3 font-medium text-foreground">{{ $debt->supplier->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $debt->invoice_no }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $debt->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($debt->total_amount) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ format_currency($debt->outstanding) }}</td>
                        <td class="px-4 py-3 text-center">
                            <x-badge variant="warning">Belum Lunas</x-badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">Semua utang supplier sudah lunas.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>

    <div>
        <h2 class="text-lg font-semibold text-foreground mb-4">Piutang dari Pembeli</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <x-card>
                <p class="text-sm text-muted-foreground">Total Sisa Piutang</p>
                <p class="text-2xl font-bold text-destructive mt-1">{{ format_currency($customerDebts->sum('outstanding')) }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-muted-foreground">Jumlah Transaksi</p>
                <p class="text-2xl font-bold text-foreground mt-1">{{ $customerDebts->count() }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-muted-foreground">Rata-rata per Transaksi</p>
                <p class="text-2xl font-bold text-foreground mt-1">{{ $customerDebts->count() > 0 ? format_currency($customerDebts->sum('outstanding') / $customerDebts->count()) : format_currency(0) }}</p>
            </x-card>
        </div>

        <x-data-table>
            <x-slot:header>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Pelanggan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Invoice</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Sisa Piutang</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                </tr>
            </x-slot:header>
            <tbody>
                @forelse($customerDebts as $s)
                    <tr class="hover:bg-muted/50 transition">
                        <td class="px-4 py-3 font-medium text-foreground">{{ $s->customer_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $s->invoice_no }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $s->sale_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($s->total_amount) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ format_currency($s->outstanding) }}</td>
                        <td class="px-4 py-3 text-center">
                            <x-badge variant="warning">Belum Lunas</x-badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">Semua piutang sudah lunas.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
@endsection
