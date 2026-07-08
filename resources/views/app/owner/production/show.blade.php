@extends('app.layouts.app')
@section('title', 'Detail Produksi')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-card>
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">Detail Produksi</h2>
                <p class="text-sm text-muted-foreground">
                    {{ $production->product->name }} — {{ $production->branch->name }}
                </p>
            </div>
            <div>
                @switch($production->status)
                    @case('confirmed')<x-badge variant="success">Confirmed</x-badge>@break
                    @case('draft')<x-badge variant="warning">Draft</x-badge>@break
                    @case('cancelled')<x-badge variant="danger">Cancelled</x-badge>@break
                @endswitch
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm p-4 bg-muted rounded-[var(--radius)] mb-4">
            <div><span class="text-muted-foreground">Produk:</span> <strong>{{ $production->product->name }}</strong></div>
            <div><span class="text-muted-foreground">Cabang:</span> <strong>{{ $production->branch->name }}</strong></div>
            <div><span class="text-muted-foreground">Qty Target:</span> <strong>{{ format_number($production->quantity_target) }}</strong></div>
            <div><span class="text-muted-foreground">Dibuat oleh:</span> <strong>{{ $production->user->name }}</strong></div>
            <div><span class="text-muted-foreground">Tanggal:</span> <strong>{{ $production->created_at->format('d M Y H:i') }}</strong></div>
            @if($production->produced_at)
                <div><span class="text-muted-foreground">Diproduksi:</span> <strong>{{ $production->produced_at->format('d M Y H:i') }}</strong></div>
            @endif
        </div>

        @if($production->consumptions->count())
            <h3 class="text-sm font-semibold text-foreground mb-3">Breakdown Konsumsi Bahan Baku (FEFO)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted border-b border-border">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs text-muted-foreground">Bahan Baku</th>
                            <th class="px-3 py-2 text-left text-xs text-muted-foreground">Batch</th>
                            <th class="px-3 py-2 text-right text-xs text-muted-foreground">Qty Terpakai</th>
                            <th class="px-3 py-2 text-left text-xs text-muted-foreground">Expired</th>
                            <th class="px-3 py-2 text-right text-xs text-muted-foreground">Sisa Batch</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($production->consumptions as $c)
                            <tr class="hover:bg-muted/50">
                                <td class="px-3 py-2">{{ $c->rawMaterialBatch->rawMaterial->name }}</td>
                                <td class="px-3 py-2 font-medium">{{ $c->rawMaterialBatch->batch_no }}</td>
                                <td class="px-3 py-2 text-right">{{ format_number($c->quantity_deducted) }}</td>
                                <td class="px-3 py-2">{{ $c->rawMaterialBatch->expired_date?->format('d M Y') ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ format_number($c->rawMaterialBatch->quantity_remaining) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($production->status === 'confirmed')
            <p class="text-sm text-muted-foreground py-2">Tidak ada data konsumsi (produksi mungkin menggunakan produk tanpa resep).</p>
        @endif
    </x-card>

    <div><a href="{{ route('app.production.index') }}"><x-button variant="secondary" type="button">Kembali</x-button></a></div>
</div>
@endsection
