<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $orders;

    public function __construct(Collection $orders)
    {
        $this->orders = $orders;
    }

    public function collection(): Collection
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return ['Kode Produksi', 'Produk', 'Resep', 'Quantity Target', 'Multiplier', 'Cabang', 'Tanggal', 'Status'];
    }

    public function map($o): array
    {
        return [
            $o->production_code,
            $o->product->name ?? '-',
            $o->recipe->name ?? '-',
            (float) $o->quantity_target,
            (float) $o->batch_multiplier,
            $o->branch->name ?? '-',
            $o->produced_at?->format('d/m/Y H:i') ?? $o->created_at->format('d/m/Y H:i'),
            $o->status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
