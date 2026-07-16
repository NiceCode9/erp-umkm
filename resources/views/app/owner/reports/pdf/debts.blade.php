<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Utang &amp; Piutang</title>
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
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Utang &amp; Piutang</h1>
        <p>
            @if($dateFrom && $dateTo)
                Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            @else
                Semua Periode
            @endif
        </p>
    </div>

    {{-- ====== UTANG SUPPLIER ====== --}}
    <div class="section-title">Utang ke Supplier</div>

    <div class="summary">
        <div class="summary-box">
            <div class="label">Total Utang</div>
            <div class="value">{{ format_currency($debtSummary['total'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Sudah Dibayar</div>
            <div class="value">{{ format_currency($debtSummary['paid'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Sisa Belum Dibayar</div>
            <div class="value">{{ format_currency($debtSummary['outstanding'] ?? 0) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th class="text-right">Total</th>
                <th class="text-right">Dibayar</th>
                <th class="text-right">Sisa</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payables as $debt)
                <tr>
                    <td>{{ $debt->supplier->name ?? '-' }}</td>
                    <td>{{ $debt->invoice_no }}</td>
                    <td>{{ $debt->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-right">{{ format_currency($debt->total_amount) }}</td>
                    <td class="text-right">{{ format_currency($debt->paidAmount()) }}</td>
                    <td class="text-right font-bold">{{ format_currency($debt->remainingAmount()) }}</td>
                    <td class="text-center">
                        @if($debt->payment_status === 'paid')
                            Lunas
                        @elseif($debt->payment_status === 'partial')
                            Sebagian
                        @else
                            Belum
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Tidak ada utang ke supplier</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ====== PIUTANG PEMBELI ====== --}}
    <div class="page-break"></div>
    <div class="section-title">Piutang dari Pembeli</div>

    <div class="summary">
        <div class="summary-box">
            <div class="label">Total Piutang</div>
            <div class="value">{{ format_currency($receivableSummary['total'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Sudah Diterima</div>
            <div class="value">{{ format_currency($receivableSummary['paid'] ?? 0) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Sisa Belum Diterima</div>
            <div class="value">{{ format_currency($receivableSummary['outstanding'] ?? 0) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Pelanggan</th>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th class="text-right">Total</th>
                <th class="text-right">Dibayar</th>
                <th class="text-right">Sisa</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receivables as $r)
                <tr>
                    <td>{{ $r->customer_name ?? '-' }}</td>
                    <td>{{ $r->invoice_no }}</td>
                    <td>{{ $r->sale_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-right">{{ format_currency($r->total_amount) }}</td>
                    <td class="text-right">{{ format_currency($r->paidAmount()) }}</td>
                    <td class="text-right font-bold">{{ format_currency($r->remainingAmount()) }}</td>
                    <td class="text-center">
                        @if($r->payment_status === 'paid')
                            Lunas
                        @elseif($r->payment_status === 'partial')
                            Sebagian
                        @else
                            Belum
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Tidak ada piutang dari pembeli</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
