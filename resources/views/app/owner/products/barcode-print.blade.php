@extends('app.layouts.print')

@section('title', 'Barcode ' . $product->name)

@push('scripts')
<script>
    window.print();
</script>
@endpush

@push('styles')
<style>
    @media print {
        body { margin: 0; padding: 4mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .barcode-grid { display: flex; flex-wrap: wrap; gap: 0; }
        .barcode-label { width: 50%; page-break-inside: avoid; box-sizing: border-box; padding: 4mm; text-align: center; border: 1px dashed #ddd; }
    }
    body { font-family: 'Courier New', monospace; }
    .barcode-grid { display: flex; flex-wrap: wrap; gap: 0; }
    .barcode-label { width: 50%; page-break-inside: avoid; box-sizing: border-box; padding: 4mm; text-align: center; border: 1px dashed #ddd; }
    .barcode-label p { margin: 1mm 0; font-size: 9px; color: #333; }
    .barcode-label .price { font-size: 10px; font-weight: bold; color: #000; }
</style>
@endpush

@section('content')
<div class="barcode-grid">
    @for($i = 0; $i < $qty; $i++)
        <div class="barcode-label">
            <p style="font-weight:bold;font-size:11px;">{{ $product->name }}</p>
            {!! $barcodeHtml !!}
            <p>{{ $sku }}</p>
            <p class="price">{{ format_currency($product->selling_price) }}</p>
        </div>
    @endfor
</div>
@endsection
