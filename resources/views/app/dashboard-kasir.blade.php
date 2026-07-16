@extends('app.layouts.app')

@section('title', 'Dashboard Kasir')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">Dashboard Kasir</h1>
        <p class="text-muted-foreground">Selamat datang, {{ auth()->user()->name }}!</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
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
                    <p class="text-muted-foreground text-sm mb-1">Status Shift</p>
                    <p class="text-lg font-bold {{ $activeShift ? 'text-primary' : 'text-destructive' }}">
                        {{ $activeShift ? 'Aktif (Kas: ' . format_currency($activeShift->opening_cash) . ')' : 'Belum Buka Shift' }}
                    </p>
                </div>
                <div class="text-warning text-3xl opacity-20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.022 2.559.052a1 1 0 01.92.65l.26.93a1 1 0 00.95.65h1.46c.99 0 1.8.81 1.8 1.8v1.46a1 1 0 00.65.95l1.04.44a1 1 0 01.37 1.37l-.72 1.24a1 1 0 00.37 1.37l.72.42a1 1 0 01.37 1.37l-.72 1.24a1 1 0 00.37 1.37l.72.42a1 1 0 01.37 1.37l-.72 1.24a1 1 0 00.37 1.37l.72.42a1 1 0 01.37 1.37"/></svg>
                </div>
            </div>
        </x-card>
    </div>

    <div class="bg-card border border-border rounded-[var(--radius)] p-6 shadow-sm">
        @if($todayCount > 0)
            <h2 class="text-lg font-semibold text-foreground mb-3">Ringkasan Hari Ini</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="p-4 bg-muted rounded-[var(--radius)]">
                    <p class="text-muted-foreground">Total Penjualan</p>
                    <p class="text-xl font-bold text-primary">{{ format_currency($todayTotal) }}</p>
                </div>
                <div class="p-4 bg-muted rounded-[var(--radius)]">
                    <p class="text-muted-foreground">Rata-rata per Transaksi</p>
                    <p class="text-xl font-bold text-foreground">{{ format_currency($todayAvg) }}</p>
                </div>
            </div>
        @else
            <div class="text-center text-muted-foreground py-8">
                <p>Belum ada transaksi hari ini.</p>
                @if(!$activeShift)
                    <p class="mt-2 text-sm">Buka shift terlebih dahulu untuk mulai berjualan.</p>
                    <a href="{{ route('app.kasir.shifts.open') }}" class="mt-3 inline-block"><x-button>Buka Shift</x-button></a>
                @else
                    <a href="{{ route('app.kasir.pos') }}" class="mt-3 inline-block"><x-button>Mulai Jualan</x-button></a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
