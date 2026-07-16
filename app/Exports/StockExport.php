<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $flattenedData;

    public function __construct(Collection $flattenedData)
    {
        $this->flattenedData = $flattenedData;
    }

    public function collection(): Collection
    {
        return $this->flattenedData;
    }

    public function headings(): array
    {
        return ['Tipe', 'Nama Item', 'Cabang', 'Batch No', 'Expired', 'Quantity'];
    }

    public function map($row): array
    {
        return [
            $row['type'] === 'raw_material' ? 'Bahan Baku' : 'Produk',
            $row['item_name'],
            $row['branch_name'],
            $row['batch_no'],
            $row['expired'] ?? '-',
            (float) $row['quantity'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
