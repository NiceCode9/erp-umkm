<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok</title>
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
        .item-block { margin-bottom: 20px; border: 1px solid #ddd; padding: 12px; }
        .item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .item-name { font-size: 13px; font-weight: 700; }
        .item-badge { font-size: 10px; background: #e5e7eb; padding: 2px 8px; border-radius: 3px; margin-left: 8px; }
        .item-badge.warning { background: #fef3c7; color: #92400e; }
        .item-badge.danger { background: #fee2e2; color: #991b1b; }
        .item-total { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; font-size: 11px; }
        th { background-color: #f0f0f0; font-weight: 600; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .empty { text-align: center; color: #999; padding: 20px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Stok</h1>
        <p>
            @if(!empty($branchName)) Cabang: {{ $branchName }} | @endif
            @if(!empty($itemType)) Tipe: {{ $itemType === 'raw_material' ? 'Bahan Baku' : 'Produk' }} @else Semua Tipe @endif
        </p>
    </div>

    {{-- Summary --}}
    <div class="summary">
        <div class="summary-box">
            <div class="label">Total Item</div>
            <div class="value">{{ format_number($summary['total_items'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Qty</div>
            <div class="value">{{ format_number($summary['total_qty'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Stok Rendah</div>
            <div class="value">{{ format_number($summary['low_stock_count'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Stok Habis</div>
            <div class="value">{{ format_number($summary['out_of_stock_count'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Per-Item --}}
    @forelse($stockItems as $item)
        @if(!$loop->first)
            <div class="page-break"></div>
        @endif

        <div class="item-block">
            <div class="item-header">
                <div>
                    <span class="item-name">{{ $item['name'] }}</span>
                    <span class="item-badge">{{ $item['item_type'] === 'raw_material' ? 'Bahan Baku' : 'Produk' }}</span>
                    @if(isset($item['is_low_stock']) && $item['is_low_stock'])
                        <span class="item-badge warning">Stok Rendah</span>
                    @endif
                    @if(($item['total_qty'] ?? 0) <= 0)
                        <span class="item-badge danger">Habis</span>
                    @endif
                </div>
                <div class="item-total">Total: <strong>{{ format_number($item['total_qty'] ?? 0) }}</strong></div>
            </div>

            @if(!empty($item['batches']) && count($item['batches']))
                <table>
                    <thead>
                        <tr>
                            <th>Batch No</th>
                            <th>Cabang</th>
                            <th class="text-right">Qty</th>
                            <th>Kadaluarsa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item['batches'] as $batch)
                            <tr>
                                <td>{{ $batch['batch_no'] ?? '-' }}</td>
                                <td>{{ $batch['branch_name'] ?? '-' }}</td>
                                <td class="text-right">{{ format_number($batch['quantity'] ?? 0) }}</td>
                                <td>
                                    @if(!empty($batch['expired_date']))
                                        @php
                                            $expDate = \Carbon\Carbon::parse($batch['expired_date']);
                                        @endphp
                                        {{ $expDate->format('d/m/Y') }}
                                        @if($expDate->isPast())
                                            (Kadaluarsa)
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty">Tidak ada data batch</div>
            @endif
        </div>
    @empty
        <div class="empty">Tidak ada data stok</div>
    @endforelse

</body>
</html>
