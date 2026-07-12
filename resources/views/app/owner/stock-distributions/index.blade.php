@extends('app.layouts.app')
@section('title', 'Distribusi Stok')
@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Distribusi Stok Antar Cabang</h1>
            <p class="text-muted-foreground">Pemindahan stok antar cabang</p>
        </div>
        <a href="{{ route('app.owner.stock-distributions.create') }}" class="px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius)] font-semibold hover:bg-primary/90 transition text-sm">+ Distribusi Baru</a>
    </div>

    <x-data-table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Cabang Asal</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Cabang Tujuan</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Item</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Dibuat</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Aksi</th>
            </tr>
        </x-slot:header>
        <tbody>
            @forelse($distributions as $d)
                <tr class="hover:bg-muted/50 transition">
                    <td class="px-4 py-3 font-mono text-xs">{{ $d->id }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $d->originBranch->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $d->destinationBranch->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">{{ $d->items->count() }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge variant="{{ $d->status === 'received' ? 'success' : ($d->status === 'shipped' ? 'info' : 'warning') }}">{{ $d->status }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">{{ $d->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('app.owner.stock-distributions.show', $d) }}" class="text-secondary hover:text-secondary/80 text-sm">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-muted-foreground">Belum ada distribusi stok</td></tr>
            @endforelse
        </tbody>
    </x-data-table>
    <div class="mt-4">{{ $distributions->links() }}</div>
</div>
@endsection
