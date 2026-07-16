<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $sales;

    public function __construct(Collection $sales)
    {
        $this->sales = $sales;
    }

    public function collection(): Collection
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return ['Invoice', 'Tanggal', 'Cabang', 'Kasir', 'Subtotal', 'Diskon', 'Tax', 'Total', 'Status Bayar'];
    }

    public function map($sale): array
    {
        return [
            $sale->invoice_no,
            $sale->sale_date->format('d/m/Y H:i'),
            $sale->branch->name ?? '-',
            $sale->user->name ?? '-',
            (float) $sale->subtotal,
            (float) $sale->discount_amount,
            (float) $sale->tax_amount,
            (float) $sale->total_amount,
            $sale->payment_status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
