@extends('app.layouts.app')
@section('title', 'Riwayat Pembelian')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Riwayat Pembelian</h2>
        <a href="{{ route('app.purchases.create') }}"><x-button>Pembelian Baru</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Invoice</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Tanggal</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Supplier</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Total</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status Bayar</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($purchases as $p)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $p->invoice_no }}</td>
                        <td class="px-4 py-3">{{ $p->purchase_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $p->supplier->name }}</td>
                        <td class="px-4 py-3">{{ $p->branch->name }}</td>
                        <td class="px-4 py-3">{{ format_currency($p->total_amount) }}</td>
                        <td class="px-4 py-3">
                            @switch($p->payment_status)
                                @case('paid')<x-badge variant="success">Lunas</x-badge>@break
                                @case('partial')<x-badge variant="warning">Sebagian</x-badge>@break
                                @default<x-badge variant="danger">Belum</x-badge>
                            @endswitch
                        </td>
                        <td class="px-4 py-3"><a href="{{ route('app.purchases.show', $p) }}"><x-button variant="secondary" size="sm">Detail</x-button></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Belum ada pembelian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($purchases->hasPages())<div class="mt-4">{{ $purchases->links() }}</div>@endif
</x-card>
@endsection
