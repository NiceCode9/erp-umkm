@extends('app.layouts.print')

@section('title', 'Struk #' . $sale->invoice_no)

@push('scripts')
<script>
    window.print();
</script>
@endpush

@push('styles')
<style>
    @media print {
        body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .max-w-sm { max-width: 80mm !important; margin: 0 auto; padding: 4mm; font-size: 10px; }
        table { font-size: 9px; }
        hr { margin: 2mm 0; }
        .print-actions { display: none !important; }
    }
    body { font-family: 'Courier New', monospace; }
</style>
@endpush

@section('content')
<div class="max-w-sm mx-auto bg-white p-6">
    <div class="text-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">{{ config('app.name') }}</h2>
        <p class="text-sm text-gray-500">{{ $sale->branch->name ?? '' }}</p>
        <p class="text-xs text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <hr class="mb-3 border-gray-300">

    <div class="text-sm text-gray-600 mb-3">
        <p>Invoice: <span class="font-semibold text-gray-800">{{ $sale->invoice_no }}</span></p>
        <p>Kasir: <span class="font-semibold text-gray-800">{{ $sale->user->name }}</span></p>
        @if($sale->customer_name)
            <p>Pelanggan: <span class="font-semibold text-gray-800">{{ $sale->customer_name }}</span></p>
        @endif
    </div>

    <hr class="mb-3 border-gray-300">

    <table class="w-full text-sm mb-3">
        <thead>
            <tr class="text-gray-500 text-xs">
                <th class="text-left">Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td class="py-1 pr-2 text-gray-800">
                        <span class="font-medium">{{ $item->product->name }}</span>
                        @if($item->productUnit)
                            <span class="text-xs text-gray-500">/{{ $item->productUnit->unit_name }}</span>
                        @endif
                    </td>
                    <td class="text-center text-gray-700">{{ format_number($item->quantity) }}</td>
                    <td class="text-right text-gray-700">{{ format_currency($item->unit_price) }}</td>
                    <td class="text-right font-medium text-gray-800">{{ format_currency($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="mb-2 border-gray-300">

    <div class="space-y-1 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-500">Subtotal</span>
            <span class="font-semibold text-gray-800">{{ format_currency($sale->subtotal) }}</span>
        </div>
        @if($sale->discount_amount > 0)
            <div class="flex justify-between text-orange-600">
                <span>Diskon</span>
                <span>-{{ format_currency($sale->discount_amount) }}</span>
            </div>
        @endif
        @if($sale->tax_amount > 0)
            <div class="flex justify-between text-gray-700">
                <span>Pajak ({{ format_number($sale->tax_percentage_applied) }}%)</span>
                <span>{{ format_currency($sale->tax_amount) }}</span>
            </div>
        @endif
        <hr class="border-gray-300">
        <div class="flex justify-between text-lg font-bold">
            <span>Total</span>
            <span class="text-green-600">{{ format_currency($sale->total_amount) }}</span>
        </div>
        <div class="flex justify-between text-xs text-gray-500">
            <span>Metode Bayar</span>
            <span class="font-medium capitalize text-gray-700">{{ $sale->payment_method }}</span>
        </div>
    </div>

    <hr class="my-3 border-gray-300">

    <div class="text-center text-xs text-gray-500">
        <p>Terima kasih atas kunjungan Anda</p>
        <p class="mt-1">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
    </div>
</div>

<div class="max-w-sm mx-auto mt-4 text-center print-actions">
    <a href="{{ route('app.kasir.pos') }}" class="inline-block px-6 py-3 bg-green-500 text-white rounded font-semibold hover:bg-green-600 transition">
        Transaksi Baru
    </a>
    <button onclick="window.print()" class="inline-block px-6 py-3 ml-2 border border-gray-300 rounded font-semibold hover:bg-gray-100 transition">
        Cetak Ulang
    </button>
</div>
@endsection
