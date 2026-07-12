@extends('app.layouts.app')
@section('title', 'Bayar Piutang')
@section('content')
<div class="max-w-xl mx-auto px-4 py-8">
    <x-card>
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-foreground mb-2">Bayar Piutang</h2>
            <p class="text-muted-foreground">{{ $sale->invoice_no }}</p>
        </div>

        <div class="p-4 bg-muted rounded-[var(--radius)] mb-6 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-muted-foreground">Pelanggan</span><span class="font-semibold">{{ $sale->customer_name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-muted-foreground">Cabang</span><span class="font-semibold">{{ $sale->branch->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-muted-foreground">Total</span><span>{{ format_currency($sale->total_amount) }}</span></div>
            <div class="flex justify-between"><span class="text-muted-foreground">Sudah Dibayar</span><span>{{ format_currency((float) $sale->payments->sum('amount')) }}</span></div>
            @if((float) $sale->returns->sum('total_amount') > 0)
                <div class="flex justify-between"><span class="text-muted-foreground">Retur</span><span class="text-warning">-{{ format_currency((float) $sale->returns->sum('total_amount')) }}</span></div>
            @endif
            <hr class="border-border">
            <div class="flex justify-between text-lg"><span class="font-bold">Sisa Piutang</span><span class="font-bold {{ $outstanding > 0 ? 'text-destructive' : '' }}">{{ format_currency($outstanding) }}</span></div>
        </div>

        <form method="POST" action="{{ route('app.owner.receivables.pay.store', $sale) }}" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="amount" value="Jumlah Bayar (Rp)" />
                <x-input id="amount" name="amount" type="number" class="mt-1 block w-full text-lg py-3" placeholder="0" value="{{ old('amount', $outstanding > 0 ? $outstanding : '') }}" required step="0.01" min="0.01" />
                <x-input-error :messages="$errors->get('amount')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="paid_at" value="Tanggal Bayar" />
                <x-input id="paid_at" name="paid_at" type="date" class="mt-1 block w-full" value="{{ old('paid_at', date('Y-m-d')) }}" required />
                <x-input-error :messages="$errors->get('paid_at')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="method" value="Metode Bayar" />
                <select id="method" name="method" class="mt-1 block w-full border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" required>
                    <option value="tunai" {{ old('method') === 'tunai' ? 'selected' : '' }}>Tunai</option>
                    <option value="transfer" {{ old('method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    <option value="qris" {{ old('method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                    <option value="lainnya" {{ old('method') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
                <x-input-error :messages="$errors->get('method')" class="mt-1" />
            </div>
            <x-button class="w-full justify-center py-3 text-lg">Simpan Pembayaran</x-button>
        </form>
    </x-card>
</div>
@endsection
