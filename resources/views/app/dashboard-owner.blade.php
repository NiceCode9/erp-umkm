@extends('app.layouts.app')

@section('title', 'Dashboard Owner')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">Dashboard Owner</h1>
        <p class="text-muted-foreground">Selamat datang, {{ auth()->user()->name }}!</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Penjualan Hari Ini</p>
                    <p class="text-2xl font-bold text-primary">{{ format_currency($todayTotal) }}</p>
                </div>
                <div class="text-primary text-3xl opacity-20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Transaksi Hari Ini</p>
                    <p class="text-2xl font-bold text-secondary">{{ format_number($todayCount) }}</p>
                </div>
                <div class="text-secondary text-3xl opacity-20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Total Utang Supplier</p>
                    <p class="text-2xl font-bold text-warning">{{ format_currency($outstandingPurchases->sum('outstanding')) }}</p>
                </div>
                <div class="text-warning text-3xl opacity-20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Total Piutang Pembeli</p>
                    <p class="text-2xl font-bold text-destructive">{{ format_currency($outstandingSales->sum('outstanding')) }}</p>
                </div>
                <div class="text-destructive text-3xl opacity-20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <x-card>
                <h2 class="text-lg font-semibold text-foreground mb-4">Penjualan 14 Hari Terakhir</h2>
                <div class="relative" style="height: 250px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </x-card>
        </div>
        <div>
            <x-card class="mb-4">
                <h2 class="text-lg font-semibold text-foreground mb-3">Menu Cepat</h2>
                <div class="space-y-2">
                    <a href="{{ route('app.purchases.create') }}" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">+ Pembelian Baru</a>
                    <a href="{{ route('app.raw-materials.index') }}" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">Stok Bahan Baku</a>
                    <a href="{{ route('app.production.create') }}" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">Buat Produksi</a>
                    <a href="{{ route('app.owner.reports.sales') }}" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">Laporan Keuangan</a>
                </div>
            </x-card>

            @if($todayCount > 0)
            <x-card>
                <h3 class="text-sm font-semibold text-foreground mb-2">Rangkuman Hari Ini</h3>
                <div class="text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-muted-foreground">Total</span><span class="font-semibold">{{ format_currency($todayTotal) }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Transaksi</span><span class="font-semibold">{{ format_number($todayCount) }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Rata-rata</span><span class="font-semibold">{{ format_currency($todayAvg) }}</span></div>
                </div>
            </x-card>
            @endif
        </div>
    </div>

    @if(isset($lowStockMaterials) && $lowStockMaterials->count())
        <x-card class="mb-6 border border-warning/30">
            <h2 class="text-lg font-semibold text-warning mb-3">⚠️ Stok Bahan Baku Menipis</h2>
            <div class="space-y-2">
                @foreach($lowStockMaterials as $rm)
                    <div class="flex justify-between items-center text-sm p-2 bg-warning/5 rounded-[var(--radius)]">
                        <span class="font-medium text-foreground">{{ $rm->name }}</span>
                        <span class="text-warning font-semibold">Di bawah minimum ({{ format_number($rm->minimum_stock) }} {{ $rm->base_unit }})</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if(isset($halalExpiringSoon) && $halalExpiringSoon->count())
        <x-card class="mb-6 border border-warning/30">
            <h2 class="text-lg font-semibold text-warning mb-3">Sertifikasi Halal Akan Expired</h2>
            <div class="space-y-2">
                @foreach($halalExpiringSoon as $p)
                    <div class="flex justify-between items-center text-sm p-2 bg-warning/5 rounded-[var(--radius)]">
                        <span class="font-medium text-foreground">{{ $p->name }}</span>
                        <span class="text-warning font-semibold">Exp: {{ $p->halal_cert_expired_date->format('d M Y') }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if(isset($halalExpired) && $halalExpired->count())
        <x-card class="mb-6 border border-destructive/30">
            <h2 class="text-lg font-semibold text-destructive mb-3">Sertifikasi Halal Sudah Expired</h2>
            <div class="space-y-2">
                @foreach($halalExpired as $p)
                    <div class="flex justify-between items-center text-sm p-2 bg-destructive/5 rounded-[var(--radius)]">
                        <span class="font-medium text-foreground">{{ $p->name }}</span>
                        <span class="text-destructive font-semibold">Expired: {{ $p->halal_cert_expired_date->format('d M Y') }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if(isset($outstandingPurchases) && $outstandingPurchases->count())
        <x-card class="mb-6">
            <h2 class="text-lg font-semibold text-foreground mb-3">Utang Supplier</h2>
            <div class="space-y-2">
                @foreach($outstandingPurchases->take(5) as $p)
                    <div class="flex justify-between items-center text-sm p-2 bg-warning/5 rounded-[var(--radius)]">
                        <span class="font-medium text-foreground">{{ $p->supplier->name ?? '-' }} — {{ $p->invoice_no }}</span>
                        <span class="text-warning font-semibold">{{ format_currency($p->outstanding) }}</span>
                    </div>
                @endforeach
                @if($outstandingPurchases->count() > 5)
                    <a href="{{ route('app.debts.index') }}" class="text-xs text-secondary hover:text-secondary/80 block text-center">+ {{ $outstandingPurchases->count() - 5 }} lainnya</a>
                @endif
            </div>
        </x-card>
    @endif

    @if(isset($outstandingSales) && $outstandingSales->count())
        <x-card class="mb-6">
            <h2 class="text-lg font-semibold text-foreground mb-3">Piutang Pembeli</h2>
            <div class="space-y-2">
                @foreach($outstandingSales->take(5) as $s)
                    <div class="flex justify-between items-center text-sm p-2 bg-destructive/5 rounded-[var(--radius)]">
                        <span class="font-medium text-foreground">{{ $s->customer_name ?? '-' }} — {{ $s->invoice_no }}</span>
                        <span class="text-destructive font-semibold">{{ format_currency($s->outstanding) }}</span>
                    </div>
                @endforeach
                @if($outstandingSales->count() > 5)
                    <a href="{{ route('app.reports.debts') }}" class="text-xs text-secondary hover:text-secondary/80 block text-center">+ {{ $outstandingSales->count() - 5 }} lainnya</a>
                @endif
            </div>
        </x-card>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    const chartData = @json($chartDays);
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.map(d => d.date),
            datasets: [{
                label: 'Penjualan (Rp)',
                data: chartData.map(d => d.total),
                backgroundColor: '#58CC0280',
                borderColor: '#58CC02',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', maximumFractionDigits: 0}).format(v) } }
            }
        }
    });
</script>
@endpush

@endsection
