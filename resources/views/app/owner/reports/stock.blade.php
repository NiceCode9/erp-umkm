@extends('app.layouts.app')

@section('title', 'Laporan Stok')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Laporan Stok</h1>
            <p class="text-muted-foreground">{{ $itemType === 'raw_material' ? 'Bahan Baku' : 'Produk Jadi' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.owner.reports.export', array_merge(['stock', 'excel'], request()->query())) }}"><x-button variant="secondary" size="sm">Excel</x-button></a>
            <a href="{{ route('app.owner.reports.export', array_merge(['stock', 'pdf'], request()->query())) }}"><x-button variant="danger" size="sm">PDF</x-button></a>
        </div>
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('app.owner.reports.stock') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Tipe Item</label>
                    <select name="item_type" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                        <option value="raw_material" {{ $itemType === 'raw_material' ? 'selected' : '' }}>Bahan Baku</option>
                        <option value="product" {{ $itemType === 'product' ? 'selected' : '' }}>Produk</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang</label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" size="sm">Filter</x-button>
                    <a href="{{ route('app.owner.reports.stock') }}"><x-button variant="secondary" size="sm" type="button">Reset</x-button></a>
                </div>
            </div>
        </form>
    </x-card>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-card><p class="text-sm text-muted-foreground">Total Item</p><p class="text-2xl font-bold">{{ format_number($summary['total_items']) }}</p></x-card>
        <x-card><p class="text-sm text-muted-foreground">Total Stok</p><p class="text-2xl font-bold">{{ format_number($summary['total_qty']) }}</p></x-card>
        <x-card><p class="text-sm text-muted-foreground">Stok Rendah</p><p class="text-2xl font-bold text-warning">{{ format_number($summary['low_stock']) }}</p></x-card>
    </div>

    <x-card>
        <div class="overflow-x-auto" x-data="{
            expanded: {},
            toggle(id) {
                this.expanded[id] = !this.expanded[id];
            }
        }">
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                        <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Satuan</th>
                        <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Stok Tersedia</th>
                        <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Min. Stok</th>
                        <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Batch</th>
                        <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($items as $item)
                        @php
                            $qty = (float) ($item->quantity_remaining_sum ?? 0);
                            $minStock = (float) ($item->minimum_stock ?? 0);
                            $itemId = $item->id;
                        @endphp
                        <tr class="hover:bg-muted/50">
                            <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $item->base_unit }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ format_number($qty) }}</td>
                            <td class="px-4 py-3 text-right">{{ $itemType === 'raw_material' ? format_number($minStock) : '-' }}</td>
                            <td class="px-4 py-3 text-center">{{ format_number($item->batches_count ?? 0) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($qty == 0)
                                    <x-badge variant="danger">Habis</x-badge>
                                @elseif($itemType === 'raw_material' && $qty < $minStock)
                                    <x-badge variant="warning">Rendah</x-badge>
                                @else
                                    <x-badge variant="success">Aman</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="toggle({{ $itemId }})" class="text-secondary hover:text-secondary/80 text-xs font-medium">
                                    <span x-text="expanded[{{ $itemId }}] ? 'Sembunyikan' : 'Lihat Batch'"></span>
                                </button>
                            </td>
                        </tr>
                        <tr x-show="expanded[{{ $itemId }}]" x-cloak class="bg-muted/30">
                            <td colspan="7" class="px-4 py-3">
                                @php
                                    $item->load(['batches' => function ($q) {
                                        $q->with('branch')->where('quantity_remaining', '>', 0)->orderBy('branch_id')->orderBy('expired_date');
                                    }]);
                                @endphp
                                @if($item->batches->count())
                                    <table class="w-full text-xs border border-border rounded-[var(--radius)]">
                                        <thead class="bg-muted">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Batch</th>
                                                <th class="px-3 py-2 text-left">Cabang</th>
                                                <th class="px-3 py-2 text-right">Qty</th>
                                                <th class="px-3 py-2 text-left">Expired</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border">
                                            @foreach($item->batches as $batch)
                                                @php
                                                    $isExpired = $batch->expired_date && $batch->expired_date->isPast();
                                                    $isNear = !$isExpired && $batch->expired_date && $batch->expired_date->diffInDays(now()) <= 30;
                                                @endphp
                                                <tr>
                                                    <td class="px-3 py-1.5 font-mono">{{ $batch->batch_no }}</td>
                                                    <td class="px-3 py-1.5">{{ $batch->branch->name ?? '-' }}</td>
                                                    <td class="px-3 py-1.5 text-right">{{ format_number($batch->quantity_remaining) }}</td>
                                                    <td class="px-3 py-1.5">
                                                        @if($batch->expired_date)
                                                            <span class="{{ $isExpired ? 'text-destructive' : ($isNear ? 'text-warning' : 'text-muted-foreground') }}">
                                                                {{ $batch->expired_date->format('d/m/Y') }}
                                                            </span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted-foreground text-center py-2">Tidak ada batch aktif</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())<div class="mt-4">{{ $items->links() }}</div>@endif
    </x-card>
</div>
@endsection
