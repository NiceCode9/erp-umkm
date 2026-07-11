@extends('app.layouts.app')

@section('title', 'Tutup Shift')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8">
    <x-card>
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-foreground mb-2">Tutup Shift Kasir</h2>
            <p class="text-muted-foreground">Rekonsiliasi kas sebelum menutup shift</p>
        </div>

        <div class="space-y-3 mb-6 p-4 bg-muted rounded-[var(--radius)]">
            <div class="flex justify-between">
                <span class="text-muted-foreground">Kas Awal</span>
                <span class="font-semibold">{{ format_currency($shift->opening_cash) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted-foreground">Total Penjualan Tunai</span>
                <span class="font-semibold text-primary">{{ format_currency($totalCashSales) }}</span>
            </div>
            <hr class="border-border">
            <div class="flex justify-between text-lg">
                <span class="font-semibold">Kas Sistem (Otomatis)</span>
                <span class="font-bold text-foreground">{{ format_currency($closingCashSystem) }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('app.kasir.shifts.close.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="closing_cash_actual" value="Kas Aktual (Rp)" />
                <x-input
                    id="closing_cash_actual"
                    name="closing_cash_actual"
                    type="number"
                    class="mt-1 block w-full text-lg py-3"
                    placeholder="0"
                    value="{{ old('closing_cash_actual') }}"
                    required
                    step="0.01"
                    min="0"
                />
                <x-input-error :messages="$errors->get('closing_cash_actual')" class="mt-1" />
            </div>

            <x-button class="w-full justify-center py-3 text-lg">
                Tutup Shift
            </x-button>
        </form>
    </x-card>
</div>
@endsection
