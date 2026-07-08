@extends('app.layouts.app')
@section('title', $product->name)
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <x-card>
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">{{ $product->name }}</h2>
                <p class="text-sm text-muted-foreground">
                    SKU: {{ $product->sku ?? '-' }} — Satuan: {{ $product->base_unit }} — Harga: {{ format_currency($product->selling_price) }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('app.products.recipes.index', $product) }}"><x-button variant="secondary" size="sm">Resep</x-button></a>
                <a href="{{ route('app.products.edit', $product) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
            </div>
        </div>

        @if($units->count())
            <h3 class="text-sm font-semibold text-foreground mb-2">Multi-Satuan</h3>
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($units as $u)
                    <span class="text-xs bg-muted px-3 py-1 rounded-full border border-border">
                        {{ $u->unit_name }} = {{ $u->conversion_to_base }} {{ $product->base_unit }}
                        @if($u->price_override) — {{ format_currency($u->price_override) }}@endif
                    </span>
                @endforeach
            </div>
        @endif
    </x-card>

    <x-card>
        <h3 class="text-sm font-semibold text-foreground mb-3">Stok per Cabang</h3>
        <div class="space-y-2">
            @forelse($branchData as $bd)
                <div class="flex items-center justify-between p-3 rounded-[var(--radius)] bg-muted">
                    <span class="font-medium text-foreground">{{ $bd->name }}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-foreground">Stok: {{ format_number($bd->stock) }}</span>
                        @if($bd->stock == 0)<x-badge variant="danger">Kosong</x-badge>
                        @else<x-badge variant="success">Tersedia</x-badge>@endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted-foreground py-2">Belum ada stok di cabang manapun.</p>
            @endforelse
        </div>
    </x-card>

    <x-card>
        <h3 class="text-sm font-semibold text-foreground mb-3">Riwayat Pergerakan Stok</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Tanggal</th>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Cabang</th>
                        <th class="px-3 py-2 text-center text-xs text-muted-foreground">Jenis</th>
                        <th class="px-3 py-2 text-right text-xs text-muted-foreground">Qty</th>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Sumber</th>
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($movements as $m)
                        <tr class="hover:bg-muted/50">
                            <td class="px-3 py-2 whitespace-nowrap">{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $m->branch->name }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($m->movement_type == 'in')<x-badge variant="success">Masuk</x-badge>
                                @else<x-badge variant="danger">Keluar</x-badge>@endif
                            </td>
                            <td class="px-3 py-2 text-right font-medium">{{ format_number($m->quantity) }}</td>
                            <td class="px-3 py-2 text-xs">{{ $m->reference_label }}</td>
                            <td class="px-3 py-2 text-xs text-muted-foreground">{{ $m->creator->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-muted-foreground">Belum ada pergerakan stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())<div class="mt-4">{{ $movements->links() }}</div>@endif
    </x-card>

    <div><a href="{{ route('app.products.index') }}"><x-button variant="secondary" type="button">Kembali</x-button></a></div>
</div>
@endsection
