@extends('app.layouts.app')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">Dashboard</h1>
        <p class="text-muted-foreground">Selamat datang, {{ auth()->user()->name }}!</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Total Penjualan Hari Ini</p>
                    <p class="text-2xl font-bold text-foreground">Rp 0</p>
                </div>
                <div class="text-primary text-3xl opacity-20">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>

        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Transaksi Hari Ini</p>
                    <p class="text-2xl font-bold text-foreground">0</p>
                </div>
                <div class="text-secondary text-3xl opacity-20">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>

        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Stok Kritis</p>
                    <p class="text-2xl font-bold text-warning">0</p>
                </div>
                <div class="text-warning text-3xl opacity-20">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>

        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-muted-foreground text-sm mb-1">Piutang Jatuh Tempo</p>
                    <p class="text-2xl font-bold text-destructive">Rp 0</p>
                </div>
                <div class="text-destructive text-3xl opacity-20">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-foreground mb-4">Aktivitas Terbaru</h2>
            <div class="text-center text-muted-foreground py-12">
                <i class="fas fa-inbox text-3xl mb-3 block opacity-50"></i>
                <p>Belum ada aktivitas</p>
            </div>
        </div>

        <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-foreground mb-4">Menu Cepat</h2>
            <div class="space-y-2">
                <a href="#" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">
                    <i class="fas fa-plus mr-2"></i> Transaksi Baru
                </a>
                <a href="#" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">
                    <i class="fas fa-box mr-2"></i> Lihat Stok
                </a>
                <a href="#" class="block px-4 py-2 rounded-[var(--radius)] bg-muted text-foreground hover:bg-primary hover:text-primary-foreground transition text-sm">
                    <i class="fas fa-history mr-2"></i> Riwayat Penjualan
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
