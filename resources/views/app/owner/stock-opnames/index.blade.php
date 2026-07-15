@extends('app.layouts.app')
@section('title', 'Riwayat Stok Opname')
@section('content')
<x-card>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Riwayat Stok Opname</h2>
        <a href="{{ route('app.stock-opnames.create') }}"><x-button>Stok Opname Baru</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="bg-muted border-b border-border">
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Tanggal</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Item</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Batch</th>
                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Sistem</th>
                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Aktual</th>
                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Selisih</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Alasan</th>
            </tr></thead>
            <tbody class="divide-y divide-border">
                @forelse($opnames as $o)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $o->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $o->branch->name }}</td>
                        <td class="px-4 py-3">{{ $o->item_name }}</td>
                        <td class="px-4 py-3 text-xs">{{ $o->batch_label }}</td>
                        <td class="px-4 py-3 text-right">{{ format_number($o->system_quantity) }}</td>
                        <td class="px-4 py-3 text-right">{{ format_number($o->actual_quantity) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $o->difference >= 0 ? 'text-primary' : 'text-destructive' }}">
                            {{ $o->difference >= 0 ? '+' : '' }}{{ format_number($o->difference) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">{{ $o->reason }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Belum ada opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($opnames->hasPages())<div class="mt-4">{{ $opnames->links() }}</div>@endif
</x-card>
@endsection
