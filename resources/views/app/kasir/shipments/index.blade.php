@extends('app.layouts.app')
@section('title', 'Pengiriman')
@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Pengiriman</h1>
            <p class="text-muted-foreground">Pengiriman dari transaksi Anda</p>
        </div>
    </div>

    <x-data-table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tipe</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Penerima</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Invoice</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Aksi</th>
            </tr>
        </x-slot:header>
        <tbody>
            @forelse($shipments as $s)
                <tr class="hover:bg-muted/50 transition">
                    <td class="px-4 py-3 font-mono text-xs">{{ $s->id }}</td>
                    <td class="px-4 py-3"><x-badge variant="{{ $s->type === 'borongan' ? 'info' : 'default' }}">{{ $s->type }}</x-badge></td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $s->recipient_name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $s->sale?->invoice_no ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge variant="{{ $s->status === 'delivered' ? 'success' : ($s->status === 'shipped' ? 'info' : 'warning') }}">{{ $s->status }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('app.kasir.shipments.show', $s) }}" class="text-secondary hover:text-secondary/80 text-sm">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-muted-foreground">Belum ada pengiriman</td></tr>
            @endforelse
        </tbody>
    </x-data-table>
    <div class="mt-4">{{ $shipments->links() }}</div>
</div>
@endsection
