@extends('app.layouts.app')
@section('title', 'Bayar Utang - ' . $purchase->invoice_no)
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-2">Bayar Utang Pembelian</h2>
        <p class="text-sm text-muted-foreground mb-4">Invoice: <strong>{{ $purchase->invoice_no }}</strong> — Supplier: {{ $purchase->supplier->name }}</p>

        @if($purchase->outstanding_amount <= 0)
            <div class="p-4 bg-primary/10 text-primary rounded-[var(--radius)] border border-primary/20 mb-4">
                <p class="text-sm font-medium">Utang sudah lunas.</p>
                @if($purchase->returned_amount > 0)
                    <p class="text-sm mt-1">
                        Retur Rp {{ format_number($purchase->returned_amount) }} tercatat. Bila retur melebihi sisa
                        utang, supplier owe kita — lihat bagian <strong>Kredit ke Supplier</strong> di halaman Utang.
                    </p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-muted rounded-[var(--radius)] text-sm">
            <div><span class="text-muted-foreground">Total:</span> <strong>{{ format_currency($purchase->total_amount) }}</strong></div>
            <div><span class="text-muted-foreground">Sudah dibayar:</span> <strong>{{ format_currency($purchase->paid_amount) }}</strong></div>
            <div><span class="text-muted-foreground">Retur:</span> <strong>{{ format_currency($purchase->returned_amount) }}</strong></div>
            <div><span class="text-muted-foreground">Sisa utang:</span> <strong>{{ format_currency($purchase->remainingAmount()) }}</strong></div>
            <div class="col-span-2"><span class="text-muted-foreground">Status:</span>
                @switch($purchase->payment_status)
                    @case('paid')<x-badge variant="success">Lunas</x-badge>@break
                    @case('partial')<x-badge variant="warning">Sebagian</x-badge>@break
                    @case('credit')<x-badge variant="danger">Kredit ke Supplier</x-badge>@break
                    @default<x-badge variant="danger">Belum Dibayar</x-badge>
                @endswitch
            </div>
        </div>

        <form action="{{ route('app.purchases.pay.store', $purchase) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <x-input label="Jumlah Pembayaran (Rp)" name="amount" type="number" step="0.01" min="0.01" max="{{ $purchase->remainingAmount() }}" value="{{ old('amount', $purchase->remainingAmount()) }}" :disabled="$purchase->remainingAmount() <= 0" />
                <x-input label="Tanggal" name="paid_at" type="date" value="{{ old('paid_at', date('Y-m-d')) }}" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Metode</label>
                    <select name="method" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring">
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="cheque">Cek / Giro</option>
                    </select>
                </div>
                <p class="text-xs text-muted-foreground">
                    Sisa utang tidak boleh melebihi Rp {{ format_number($purchase->remainingAmount()) }}.
                    Nilai di field pembayaran divalidasi ulang di server.
                </p>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit" :disabled="$purchase->remainingAmount() <= 0">Simpan Pembayaran</x-button>
                    <a href="{{ route('app.purchases.show', $purchase) }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection