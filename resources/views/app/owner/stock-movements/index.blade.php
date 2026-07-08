@extends('app.layouts.app')
@section('title', 'Riwayat Stok')
@section('content')
<x-card :padding="false">
    <div class="p-6 pb-4">
        <h2 class="text-lg font-semibold mb-4">Riwayat Pergerakan Stok</h2>

        <form method="GET" class="flex flex-wrap gap-3">
            <div>
                <label class="text-xs text-muted-foreground block mb-0.5">Cabang</label>
                <select name="branch_id" class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-muted-foreground block mb-0.5">Jenis</label>
                <select name="movement_type" class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                    <option value="">Semua</option>
                    <option value="in" @selected(request('movement_type') == 'in')>Masuk</option>
                    <option value="out" @selected(request('movement_type') == 'out')>Keluar</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-muted-foreground block mb-0.5">Tipe Item</label>
                <select name="item_type" class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                    <option value="">Semua</option>
                    <option value="raw_material" @selected(request('item_type') == 'raw_material')>Bahan Baku</option>
                    <option value="product" @selected(request('item_type') == 'product')>Produk</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-muted-foreground block mb-0.5">Sumber</label>
                <select name="reference_type" class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                    <option value="">Semua</option>
                    <option value="purchase" @selected(request('reference_type') == 'purchase')>Pembelian</option>
                    <option value="purchase_return" @selected(request('reference_type') == 'purchase_return')>Retur Pembelian</option>
                    <option value="production" @selected(request('reference_type') == 'production')>Produksi</option>
                    <option value="stock_opname" @selected(request('reference_type') == 'stock_opname')>Stok Opname</option>
                    <option value="sale" @selected(request('reference_type') == 'sale')>Penjualan</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-muted-foreground block mb-0.5">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
            </div>
            <div>
                <label class="text-xs text-muted-foreground block mb-0.5">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
            </div>
            <div class="flex items-end gap-2">
                <x-button size="sm" type="submit">Filter</x-button>
                <a href="{{ route('app.stock-movements.index') }}"><x-button variant="secondary" size="sm" type="button">Reset</x-button></a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-y border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Tanggal</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Item</th>
                    <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Tipe</th>
                    <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Jenis</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Qty</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Batch</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Sumber</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($movements as $m)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $m->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $m->branch->name }}</td>
                        <td class="px-4 py-3">{{ $m->item_name ?? ($m->item_type == 'raw_material' ? 'RM #'.$m->item_id : 'Prod #'.$m->item_id) }}</td>
                        <td class="px-4 py-3 text-center text-xs">
                            {{ $m->item_type == 'raw_material' ? 'Bahan Baku' : 'Produk' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($m->movement_type == 'in')
                                <x-badge variant="success">Masuk</x-badge>
                            @else
                                <x-badge variant="danger">Keluar</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-medium">{{ format_number($m->quantity) }}</td>
                        <td class="px-4 py-3 text-xs">{{ $m->batch?->batch_no ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $m->reference_label }}</td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">{{ $m->creator->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-muted-foreground">Belum ada pergerakan stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($movements->hasPages())<div class="p-4">{{ $movements->links() }}</div>@endif
</x-card>
@endsection
