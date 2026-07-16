@extends('app.layouts.app')

@section('title', 'Rekap Shift Kasir')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Rekap Shift Kasir</h1>
            <p class="text-muted-foreground">Riwayat buka/tutup shift kasir</p>
        </div>
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('app.owner.reports.shifts') }}">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang</label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Status</label>
                    <select name="status" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm bg-background">
                        <option value="">Semua</option>
                        <option value="closed" @selected(request('status') == 'closed')">Sudah Tutup</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" size="sm">Filter</x-button>
                    <a href="{{ route('app.owner.reports.shifts') }}"><x-button variant="secondary" size="sm" type="button">Reset</x-button></a>
                </div>
            </div>
        </form>
    </x-card>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-card>
            <p class="text-sm text-muted-foreground">Total Shift</p>
            <p class="text-2xl font-bold text-foreground mt-1">{{ format_number($shifts->total()) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-muted-foreground">Total Penjualan</p>
            <p class="text-2xl font-bold text-primary mt-1">{{ format_currency($shifts->sum(fn ($s) => (float) ($s->closing_cash_system ?? 0))) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-muted-foreground">Total Selisih</p>
            <p class="text-2xl font-bold text-destructive mt-1">{{ format_currency($shifts->where('closed_at')->sum(fn ($s) => (float) ($s->difference ?? 0))) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-muted-foreground">Rata-rata Kas Sistem</p>
            <p class="text-2xl font-bold text-foreground mt-1">{{ format_currency($shifts->where('closed_at')->avg(fn ($s) => (float) ($s->closing_cash_system ?? 0)) ?? 0) }}</p>
        </x-card>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                        <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Kasir</th>
                        <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Kas Awal</th>
                        <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Kas Sistem</th>
                        <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Kas Aktual</th>
                        <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Selisih</th>
                        <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($shifts as $shift)
                        <tr class="hover:bg-muted/50">
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                {{ $shift->opened_at?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="px-4 py-3">{{ $shift->branch->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $shift->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ format_currency($shift->opening_cash) }}</td>
                            <td class="px-4 py-3 text-right">{{ format_currency($shift->closing_cash_system ?? 0) }}</td>
                            <td class="px-4 py-3 text-right">{{ $shift->closing_cash_actual ? format_currency($shift->closing_cash_actual) : '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($shift->difference !== null)
                                    <span class="{{ $shift->difference != 0 ? 'text-destructive font-semibold' : 'text-primary font-semibold' }}">
                                        {{ format_currency($shift->difference) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($shift->closed_at)
                                    <x-badge variant="success">Selesai</x-badge>
                                @else
                                    <x-badge variant="warning">Aktif</x-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data shift.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shifts->hasPages())<div class="mt-4">{{ $shifts->links() }}</div>@endif
    </x-card>
</div>
@endsection
