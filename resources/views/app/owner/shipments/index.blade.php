@extends('app.layouts.app')
@section('title', 'Pengiriman')
@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Pengiriman ke Pembeli</h1>
            <p class="text-muted-foreground">Kelola pengiriman barang</p>
        </div>
        <a href="{{ route('app.owner.shipments.create') }}" class="px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius)] font-semibold hover:bg-primary/90 transition text-sm">+ Pengiriman Baru</a>
    </div>

    <x-data-table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tipe</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Cabang</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tujuan</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Invoice</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tgl Kirim</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Aksi</th>
            </tr>
        </x-slot:header>
        <tbody>
            @forelse($shipments as $s)
                <tr class="hover:bg-muted/50 transition">
                    <td class="px-4 py-3 font-mono text-xs">{{ $s->id }}</td>
                    <td class="px-4 py-3"><x-badge variant="{{ $s->type === 'borongan' ? 'info' : 'default' }}">{{ $s->type }}</x-badge></td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $s->branch->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground max-w-[200px] truncate">{{ $s->destination }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $s->sale?->invoice_no ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge variant="{{ $s->status === 'delivered' ? 'success' : ($s->status === 'shipped' ? 'info' : 'warning') }}">{{ $s->status }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">{{ $s->shipped_at?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('app.owner.shipments.show', $s) }}" class="text-secondary hover:text-secondary/80 text-sm">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-muted-foreground">Belum ada pengiriman</td></tr>
            @endforelse
        </tbody>
    </x-data-table>
    <div class="mt-4">{{ $shipments->links() }}</div>
</div>
@endsection
