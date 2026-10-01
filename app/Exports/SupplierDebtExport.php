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
        // Kolom "Sudah Dibayar" & "Retur" sengaja dipisah. Sebelumnya keduanya
        // dijumlahkan ke satu kolom sehingga nilai retur tersamar sebagai
        // pembayaran (BUG: kolom berlabel "Sudah Dibayar" menampilkan
        // payments + returns).
        return ['Invoice', 'Supplier', 'Cabang', 'Tanggal', 'Total', 'Sudah Dibayar', 'Retur', 'Sisa Utang', 'Status'];
    }

    public function map($p): array
    {
        return [
            $p->invoice_no,
            $p->supplier->name ?? '-',
            $p->branch->name ?? '-',
            $p->purchase_date?->format('d/m/Y'),
            (float) $p->total_amount,
            (float) ($p->paid_amount ?? 0),
            (float) ($p->returned_amount ?? 0),
            (float) ($p->outstanding ?? 0),
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
