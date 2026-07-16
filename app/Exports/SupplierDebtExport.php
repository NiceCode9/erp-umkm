<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierDebtExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $debts;

    public function __construct(Collection $debts)
    {
        $this->debts = $debts;
    }

    public function collection(): Collection
    {
        return $this->debts;
    }

    public function headings(): array
    {
        return ['Invoice', 'Supplier', 'Cabang', 'Tanggal', 'Total', 'Sudah Dibayar', 'Sisa Utang', 'Status'];
    }

    public function map($p): array
    {
        return [
            $p->invoice_no,
            $p->supplier->name ?? '-',
            $p->branch->name ?? '-',
            $p->purchase_date?->format('d/m/Y'),
            (float) $p->total_amount,
            (float) ($p->payments->sum('amount') + $p->returns->sum('total_amount')),
            $p->outstanding ?? 0,
            $p->payment_status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
