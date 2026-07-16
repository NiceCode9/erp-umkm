@extends('app.layouts.app')
@section('title', 'Stok Opname')
@section('content')
<x-card>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Sesi Stok Opname</h2>
        <a href="{{ route('app.stock-opnames.create') }}"><x-button>Buat Sesi Baru</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="bg-muted border-b border-border">
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Tanggal</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Judul</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Tipe</th>
                <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Oleh</th>
                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-border">
                @forelse($sessions as $s)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $s->opname_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $s->title ?? 'Sesi #'.$s->id }}</td>
                        <td class="px-4 py-3">{{ $s->branch->name }}</td>
                        <td class="px-4 py-3">{{ $s->item_type === 'raw_material' ? 'Bahan Baku' : 'Produk' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($s->status === 'draft')<x-badge variant="warning">Draft</x-badge>
                            @else<x-badge variant="success">Confirmed</x-badge>@endif
                        </td>
                        <td class="px-4 py-3">{{ $s->user->name }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('app.stock-opnames.worksheet', $s) }}"><x-button variant="secondary" size="sm">Buka</x-button></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Belum ada sesi opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($sessions->hasPages())<div class="mt-4">{{ $sessions->links() }}</div>@endif
</x-card>
@endsection
