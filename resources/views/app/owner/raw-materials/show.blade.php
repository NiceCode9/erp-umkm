@extends('app.layouts.app')
@section('title', $rawMaterial->name)
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <x-card>
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">{{ $rawMaterial->name }}</h2>
                <p class="text-sm text-muted-foreground">Satuan: {{ $rawMaterial->base_unit }} — Minimum stok: {{ $rawMaterial->minimum_stock }}</p>
            </div>
            <a href="{{ route('app.raw-materials.edit', $rawMaterial) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
        </div>

        <h3 class="text-sm font-semibold text-foreground mb-3">Batch Aktif per Cabang</h3>
        @if($batches->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted border-b border-border">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs text-muted-foreground">Cabang</th>
                            <th class="px-3 py-2 text-left text-xs text-muted-foreground">Batch</th>
                            <th class="px-3 py-2 text-right text-xs text-muted-foreground">Sisa Stok</th>
                            <th class="px-3 py-2 text-right text-xs text-muted-foreground">Harga Beli</th>
                            <th class="px-3 py-2 text-left text-xs text-muted-foreground">Expired</th>
                            <th class="px-3 py-2 text-left text-xs text-muted-foreground">Diterima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($batches as $batch)
                            <tr class="hover:bg-muted/50">
                                <td class="px-3 py-2">{{ $batch->branch->name }}</td>
                                <td class="px-3 py-2 font-medium">{{ $batch->batch_no }}</td>
                                <td class="px-3 py-2 text-right">{{ format_number($batch->quantity_remaining) }}</td>
                                <td class="px-3 py-2 text-right">{{ format_currency($batch->purchase_price) }}</td>
                                <td class="px-3 py-2">{{ $batch->expired_date?->format('d M Y') ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $batch->received_at?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-muted-foreground py-4">Tidak ada batch aktif.</p>
        @endif
    </x-card>

    <x-card>
        <h3 class="text-sm font-semibold text-foreground mb-3">Stok per Cabang</h3>
        <div class="space-y-2">
            @forelse($branchData as $bd)
                <div class="flex items-center justify-between p-3 rounded-[var(--radius)] {{ $bd->below_minimum ? 'bg-warning/5 border border-warning/20' : 'bg-muted' }}">
                    <div>
                        <span class="font-medium text-foreground">{{ $bd->name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm {{ $bd->below_minimum ? 'text-warning font-semibold' : 'text-foreground' }}">
                            Stok: {{ format_number($bd->stock) }}
                        </span>
                        @if($bd->below_minimum)
                            <x-badge variant="warning">Di bawah minimum</x-badge>
                        @elseif($bd->stock == 0)
                            <x-badge variant="danger">Kosong</x-badge>
                        @else
                            <x-badge variant="success">Aman</x-badge>
                        @endif
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
                        <th class="px-3 py-2 text-left text-xs text-muted-foreground">Batch</th>
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
                                @if($m->movement_type == 'in')
                                    <x-badge variant="success">Masuk</x-badge>
                                @else
                                    <x-badge variant="danger">Keluar</x-badge>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-medium">{{ format_number($m->quantity) }}</td>
                            <td class="px-3 py-2 text-xs">{{ $m->batch?->batch_no ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $m->reference_label }}</td>
                            <td class="px-3 py-2 text-xs text-muted-foreground">{{ $m->creator->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-muted-foreground">Belum ada pergerakan stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())<div class="mt-4">{{ $movements->links() }}</div>@endif
    </x-card>

    <div><a href="{{ route('app.raw-materials.index') }}"><x-button variant="secondary" type="button">Kembali</x-button></a></div>
</div>
@endsection
