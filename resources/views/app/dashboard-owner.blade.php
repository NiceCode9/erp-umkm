@extends('app.layouts.app')

@section('title', 'Dashboard Owner')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">Dashboard Owner</h1>
        <p class="text-muted-foreground">Selamat datang, {{ auth()->user()->name }}!</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Total Penjualan Hari Ini</p>
                    <p class="text-2xl font-bold text-foreground">Rp 0</p>
                </div>
                <div class="text-primary text-3xl opacity-20"><i class="fas fa-shopping-cart"></i></div>
            </div>
        </div>
        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Transaksi Hari Ini</p>
                    <p class="text-2xl font-bold text-foreground">0</p>
                </div>
                <div class="text-secondary text-3xl opacity-20"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Stok Kritis</p>
                    <p class="text-2xl font-bold text-warning">0</p>
                </div>
                <div class="text-warning text-3xl opacity-20"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Piutang Jatuh Tempo</p>
                    <p class="text-2xl font-bold text-destructive">Rp 0</p>
                </div>
                <div class="text-destructive text-3xl opacity-20"><i class="fas fa-clock"></i></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-foreground mb-4">Aktivitas Terbaru</h2>
            <div class="text-center text-muted-foreground py-12">
                <p>Belum ada aktivitas</p>
            </div>
        </div>
        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-foreground mb-4">Menu Cepat</h2>
            <div class="space-y-2">
                <a href="{{ route('app.purchases.create') }}" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">+ Pembelian Baru</a>
                <a href="{{ route('app.raw-materials.index') }}" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">Lihat Stok Bahan Baku</a>
                <a href="{{ route('app.purchases.index') }}" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">Riwayat Pembelian</a>
            </div>
        </div>
    </div>

    @if(isset($lowStockMaterials) && $lowStockMaterials->count())
        <x-card class="mt-6 border-warning/30">
            <h2 class="text-lg font-semibold text-warning mb-3">⚠️ Stok Bahan Baku Menipis</h2>
            <div class="space-y-2">
                @foreach($lowStockMaterials as $rm)
                    <div class="flex justify-between items-center text-sm p-2 bg-warning/5 rounded-[var(--radius)]">
                        <span class="font-medium text-foreground">{{ $rm->name }}</span>
                        <span class="text-warning font-semibold">Stok saat ini di bawah minimum ({{ $rm->minimum_stock }} {{ $rm->base_unit }})</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">
                <a href="{{ route('app.purchases.create') }}" class="text-sm text-secondary hover:text-secondary/80">→ Beli stok sekarang</a>
            </div>
        </x-card>
    @endif
</div>
@endsection
