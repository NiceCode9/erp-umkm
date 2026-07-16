<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; padding: 30px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header p { font-size: 11px; color: #666; margin-top: 4px; }
        .summary { display: flex; gap: 20px; margin-bottom: 20px; }
        .summary-box { border: 1px solid #ccc; padding: 10px 14px; flex: 1; }
        .summary-box .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .summary-box .value { font-size: 16px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 7px 10px; text-align: left; font-size: 11px; }
        th { background-color: #f0f0f0; font-weight: 600; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .section-title { font-size: 14px; font-weight: 600; margin: 18px 0 8px; }
        .empty { text-align: center; color: #999; padding: 20px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Penjualan</h1>
        <p>
            @if($dateFrom && $dateTo)
                Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            @else
                Semua Periode
            @endif
            @if(!empty($branchName)) | Cabang: {{ $branchName }} @endif
        </p>
    </div>

    {{-- Summary --}}
    <div class="summary">
        <div class="summary-box">
            <div class="label">Total Penjualan</div>
            <div class="value">{{ format_currency($summary['total_sales'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Jumlah Transaksi</div>
            <div class="value">{{ format_number($summary['total_transactions'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Rata-rata / Transaksi</div>
            <div class="value">{{ format_currency($summary['average_per_transaction'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Top Products --}}
    @if(isset($topProducts) && count($topProducts))
        <div class="section-title">Produk Terlaris</div>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>Produk</th>
                    <th class="text-right">Qty Terjual</th>
                    <th class="text-right">Total Omzet</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $p['name'] }}</td>
                        <td class="text-right">{{ format_number($p['total_qty'] ?? 0) }}</td>
                        <td class="text-right font-bold">{{ format_currency($p['total_revenue'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Detail Table --}}
    <div class="section-title">Detail Transaksi</div>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Pajak</th>
                <th class="text-right">Total</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->invoice_no }}</td>
                    <td>{{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $sale->user->name ?? '-' }}</td>
                    <td class="text-right">{{ format_currency($sale->subtotal) }}</td>
                    <td class="text-right">{{ format_currency($sale->discount_amount) }}</td>
                    <td class="text-right">{{ format_currency($sale->tax_amount) }}</td>
                    <td class="text-right font-bold">{{ format_currency($sale->total_amount) }}</td>
                    <td class="text-center">{{ ucfirst($sale->payment_status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Tidak ada data penjualan</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
