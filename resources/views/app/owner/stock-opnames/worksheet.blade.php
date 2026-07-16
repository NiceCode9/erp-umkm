@extends('app.layouts.app')
@section('title', 'Stok Opname: ' . ($session->title ?? $session->id))
@section('content')

@if($session->status === 'confirmed')
    <div class="mb-4 p-4 bg-primary/10 text-primary rounded-[var(--radius)] border border-primary/20">
        Sesi ini sudah dikunci pada {{ $session->confirmed_at?->format('d M Y H:i') }}. Data hanya bisa dilihat.
    </div>
@endif

<div class="max-w-6xl mx-auto">
    <x-card :padding="false">
        <div class="p-6 pb-4 border-b border-border flex justify-between items-start">
            <div>
                <h2 class="text-lg font-semibold">
                    @if($session->title){{ $session->title }}@else Sesi Opname #{{ $session->id }}@endif
                    @if($session->status === 'draft')<x-badge variant="warning">Draft</x-badge>
                    @else<x-badge variant="success">Confirmed</x-badge>@endif
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ $session->branch->name }} — {{ $session->item_type === 'raw_material' ? 'Bahan Baku' : 'Produk' }}
                    — {{ $session->opname_date->format('d M Y') }}
                    — {{ count($batches) }} batch
                </p>
            </div>
            <a href="{{ route('app.stock-opnames.index') }}"><x-button variant="secondary" size="sm">Kembali</x-button></a>
        </div>

        @if($session->status === 'draft')
        <form method="POST" action="{{ route('app.stock-opnames.worksheet.save', $session) }}">
            @csrf
        @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Item</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Batch</th>
                            <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Expired</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Stok Sistem</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted-foreground w-32">Stok Aktual</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($batches as $i => $b)
                            @php
                                $existing = $existingRecords->get($b['batch_id']);
                                $actual = $existing ? (float) $existing->actual_quantity : (float) $b['system_qty'];
                                $diff = $actual - $b['system_qty'];
                            @endphp
                            <tr class="hover:bg-muted/50 {{ $diff != 0 ? 'bg-warning/5' : '' }}">
                                <td class="px-4 py-2 font-medium">{{ $b['item_name'] }}</td>
                                <td class="px-4 py-2 text-xs">{{ $b['batch_no'] }}</td>
                                <td class="px-4 py-2 text-xs text-center">{{ $b['expired'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ format_number($b['system_qty']) }}</td>
                                <td class="px-4 py-2">
                                    @if($session->status === 'draft')
                                        <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $b['item_id'] }}">
                                        <input type="hidden" name="items[{{ $i }}][batch_id]" value="{{ $b['batch_id'] }}">
                                        <input type="hidden" name="items[{{ $i }}][system_qty]" value="{{ $b['system_qty'] }}">
                                        <input type="number" name="items[{{ $i }}][actual_qty]" value="{{ $actual }}" step="0.01"
                                            class="w-full border border-input rounded-[var(--radius)] px-3 py-1.5 text-sm bg-background text-right"
                                            onchange="this.closest('tr').classList.toggle('bg-warning/5', parseFloat(this.value) != {{ $b['system_qty'] }})">
                                    @else
                                        {{ format_number($actual) }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right font-semibold {{ $diff > 0 ? 'text-primary' : ($diff < 0 ? 'text-destructive' : '') }}">
                                    {{ $diff > 0 ? '+' : '' }}{{ format_number($diff) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Tidak ada batch aktif untuk kategori ini di cabang ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($session->status === 'draft')
            <div class="p-4 border-t border-border flex gap-3">
                <x-button type="submit">Simpan Draft</x-button>
                @if($batches->count())
                    <x-button type="submit" formaction="{{ route('app.stock-opnames.confirm', $session) }}" variant="danger">Kunci & Konfirmasi</x-button>
                @endif
            </div>
        </form>
        @endif
    </x-card>
</div>
@endsection
