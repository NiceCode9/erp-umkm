<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Produksi</title>
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
        .empty { text-align: center; color: #999; padding: 20px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Produksi</h1>
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
            <div class="label">Total Order</div>
            <div class="value">{{ format_number($summary['total_orders'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Confirmed</div>
            <div class="value">{{ format_number($summary['confirmed_count'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Draft</div>
            <div class="value">{{ format_number($summary['draft_count'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Cancelled</div>
            <div class="value">{{ format_number($summary['cancelled_count'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Produk</th>
                <th>Resep</th>
                <th class="text-right">Qty Target</th>
                <th>Cabang</th>
                <th>Tanggal</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->code }}</td>
                    <td>{{ $order->product->name ?? '-' }}</td>
                    <td>{{ $order->recipe->name ?? '-' }}</td>
                    <td class="text-right">{{ format_number($order->quantity_target) }}</td>
                    <td>{{ $order->branch->name ?? '-' }}</td>
                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                    <td class="text-center">
                        @if($order->status === 'confirmed')
                            Confirmed
                        @elseif($order->status === 'draft')
                            Draft
                        @elseif($order->status === 'cancelled')
                            Cancelled
                        @else
                            {{ $order->status }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Tidak ada data produksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
