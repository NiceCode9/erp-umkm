@extends('app.layouts.app')
@section('title', 'Riwayat Produksi')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Riwayat Produksi</h2>
        <a href="{{ route('app.production.create') }}"><x-button>Produksi Baru</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Tanggal</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Produk</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Qty Target</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Oleh</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($orders as $o)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $o->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $o->product->name }}</td>
                        <td class="px-4 py-3">{{ $o->branch->name }}</td>
                        <td class="px-4 py-3 text-right">{{ format_number($o->quantity_target) }}</td>
                        <td class="px-4 py-3">
                            @switch($o->status)
                                @case('confirmed')<x-badge variant="success">Confirmed</x-badge>@break
                                @case('draft')<x-badge variant="warning">Draft</x-badge>@break
                                @case('cancelled')<x-badge variant="danger">Cancelled</x-badge>@break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $o->user->name }}</td>
                        <td class="px-4 py-3"><a href="{{ route('app.production.show', $o) }}"><x-button variant="secondary" size="sm">Detail</x-button></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Belum ada produksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($orders->hasPages())<div class="mt-4">{{ $orders->links() }}</div>@endif
</x-card>
@endsection
