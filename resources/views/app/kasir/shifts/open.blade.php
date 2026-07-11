@extends('app.layouts.app')

@section('title', 'Buka Shift')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8">
    <x-card>
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-foreground mb-2">Buka Shift Kasir</h2>
            <p class="text-muted-foreground">Masukkan jumlah kas awal sebelum memulai transaksi</p>
        </div>

        <form method="POST" action="{{ route('app.kasir.shifts.open.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="opening_cash" value="Kas Awal (Rp)" />
                <x-input
                    id="opening_cash"
                    name="opening_cash"
                    type="number"
                    class="mt-1 block w-full text-lg py-3"
                    placeholder="0"
                    value="{{ old('opening_cash') }}"
                    required
                    step="0.01"
                    min="0"
                />
                <x-input-error :messages="$errors->get('opening_cash')" class="mt-1" />
            </div>

            <x-button class="w-full justify-center py-3 text-lg">
                Buka Shift
            </x-button>
        </form>
    </x-card>
</div>
@endsection
