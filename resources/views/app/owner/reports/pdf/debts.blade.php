<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Utang Supplier</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; padding: 30px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header p { font-size: 11px; color: #666; margin-top: 4px; }
        .summary { display: flex; gap: 20px; margin-bottom: 16px; }
        .summary-box { border: 1px solid #ccc; padding: 10px 14px; flex: 1; }
        .summary-box .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .summary-box .value { font-size: 16px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 7px 10px; text-align: left; font-size: 11px; }
        th { background-color: #f0f0f0; font-weight: 600; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .section-title { font-size: 14px; font-weight: 600; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #ddd; }
        .empty { text-align: center; color: #999; padding: 20px; }
        .page-break { page-break-before: always; }
        tfoot td { background-color: #f7f7f7; font-weight: 700; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Utang Supplier</h1>
        <p>
            {{ $businessName ?: '-' }}
            @if(isset($generatedAt)) &mdash; Dibuat: {{ $generatedAt->format('d/m/Y H:i') }} @endif
        </p>
    </div>

    {{-- ====== UTANG OUTSTANDING ====== --}}
    <div class="section-title">Utang Outstanding</div>

    <div class="summary">
        <div class="summary-box">
            <div class="label">Jumlah Transaksi</div>
            <div class="value">{{ $rows->count() }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Sisa Utang</div>
            <div class="value">{{ format_currency($grandTotal) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Kredit ke Supplier</div>
            <div class="value">{{ format_currency($creditTotal) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Supplier</th>
                <th>Cabang</th>
                <th class="text-right">Total</th>
                <th class="text-right">Dibayar</th>
                <th class="text-right">Retur</th>
                <th class="text-right">Sisa Utang</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $p)
                <tr>
                    <td>{{ $p->invoice_no }}</td>
                    <td>{{ $p->supplier->name ?? '-' }}</td>
                    <td>{{ $p->branch->name ?? '-' }}</td>
                    <td class="text-right">{{ format_currency($p->total_amount) }}</td>
                    <td class="text-right">{{ format_currency($p->paid_amount) }}</td>
                    <td class="text-right">{{ format_currency($p->returned_amount) }}</td>
                    <td class="text-right font-bold">{{ format_currency($p->outstanding) }}</td>
                    <td class="text-center">
                        @if($p->payment_status === 'paid')
                            Lunas
                        @elseif($p->payment_status === 'partial')
                            Sebagian
                        @else
                            Belum
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Tidak ada utang ke supplier</td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->count())
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right">TOTAL</td>
                    <td class="text-right">{{ format_currency($grandTotal) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- ====== KREDIT KE SUPPLIER ====== --}}
    @if($creditRows->count())
        <div class="page-break"></div>
        <div class="section-title">Kredit / Klaim ke Supplier</div>
        <p style="font-size:11px;color:#666;margin-bottom:8px;">
            Retur pembelian melebihi sisa utang, sehingga supplier owes kita.
        </p>

        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Supplier</th>
                    <th>Cabang</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Dibayar</th>
                    <th class="text-right">Retur</th>
                    <th class="text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creditRows as $p)
                    <tr>
                        <td>{{ $p->invoice_no }}</td>
                        <td>{{ $p->supplier->name ?? '-' }}</td>
                        <td>{{ $p->branch->name ?? '-' }}</td>
                        <td class="text-right">{{ format_currency($p->total_amount) }}</td>
                        <td class="text-right">{{ format_currency($p->paid_amount) }}</td>
                        <td class="text-right">{{ format_currency($p->returned_amount) }}</td>
                        <td class="text-right font-bold">{{ format_currency(abs($p->outstanding)) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right">TOTAL KREDIT</td>
                    <td class="text-right">{{ format_currency($creditTotal) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

</body>
</html>