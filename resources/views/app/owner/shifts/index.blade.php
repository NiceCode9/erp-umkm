@extends('app.layouts.app')

@section('title', 'Rekap Shift')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Rekap Shift Kasir</h1>
            <p class="text-muted-foreground">Riwayat shift seluruh kasir</p>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-xs text-muted-foreground block mb-1">Cabang</label>
            <select name="branch_id" class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:border-transparent">
                <option value="">Semua Cabang</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $shift == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-muted-foreground block mb-1">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:border-transparent" />
        </div>
        <div>
            <label class="text-xs text-muted-foreground block mb-1">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                class="border border-input rounded-[var(--radius)] px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:border-transparent" />
        </div>
        <x-button type="submit" variant="primary" class="py-2">Filter</x-button>
        <a href="{{ route('app.owner.shifts.index') }}" class="px-4 py-2 border border-border rounded-[var(--radius)] text-sm hover:bg-muted transition">Reset</a>
    </form>

    <x-data-table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Kasir</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Cabang</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Buka</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tutup</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Kas Awal</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Sistem</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Aktual</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Selisih</th>
            </tr>
        </x-slot:header>
        <tbody>
            @forelse($shifts as $s)
                <tr class="hover:bg-muted/50 transition">
                    <td class="px-4 py-3 font-medium text-foreground">{{ $s->user->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $s->branch->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $s->opened_at ? $s->opened_at->format('d/m/Y H:i') : '-' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $s->closed_at ? $s->closed_at->format('d/m/Y H:i') : '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ format_currency($s->opening_cash) }}</td>
                    <td class="px-4 py-3 text-right">{{ $s->closing_cash_system !== null ? format_currency($s->closing_cash_system) : '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ $s->closing_cash_actual !== null ? format_currency($s->closing_cash_actual) : '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $s->difference && abs($s->difference) > 0 ? ($s->difference < 0 ? 'text-destructive' : 'text-warning') : '' }}">
                        {{ $s->difference !== null ? format_currency($s->difference) : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">Belum ada data shift</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-4">
        {{ $shifts->links() }}
    </div>
</div>
@endsection
