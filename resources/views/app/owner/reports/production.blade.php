@extends('app.layouts.app')

@section('title', 'Laporan Produksi')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Laporan Produksi</h1>
            <p class="text-muted-foreground">Riwayat order produksi</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.owner.reports.export', array_merge(['production', 'excel'], request()->query())) }}">
                <x-button variant="secondary" size="sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </x-button>
            </a>
            <a href="{{ route('app.owner.reports.export', array_merge(['production', 'pdf'], request()->query())) }}">
                <x-button variant="danger" size="sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF
                </x-button>
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('app.owner.reports.production') }}">
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
                    <a href="{{ route('app.owner.reports.production') }}"><x-button variant="secondary" size="sm" type="button">Reset</x-button></a>
                </div>
            </div>
        </form>
    </x-card>

    {{-- Production Orders Table --}}
    <x-data-table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Kode</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Produk</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Resep</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Qty Target</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Cabang</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tanggal</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
            </tr>
        </x-slot:header>
        <tbody>
            @forelse($orders as $order)
                <tr class="hover:bg-muted/50 transition">
                    <td class="px-4 py-3 font-mono text-xs font-medium text-foreground">{{ $order->code }}</td>
                    <td class="px-4 py-3 font-medium text-foreground">{{ $order->product->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $order->recipe->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ format_number($order->quantity_target) }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $order->branch->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">{{ $order->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        @switch($order->status)
                            @case('confirmed')
                                <x-badge variant="success">Confirmed</x-badge>
                                @break
                            @case('draft')
                                <x-badge variant="warning">Draft</x-badge>
                                @break
                            @case('cancelled')
                                <x-badge variant="danger">Cancelled</x-badge>
                                @break
                            @default
                                <x-badge variant="default">{{ $order->status }}</x-badge>
                        @endswitch
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">Tidak ada data produksi untuk filter yang dipilih</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
@endsection
