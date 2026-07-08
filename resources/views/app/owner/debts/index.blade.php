@extends('app.layouts.app')
@section('title', 'Utang ke Supplier')
@section('content')
<x-card>
    <h2 class="text-lg font-semibold mb-4">Daftar Utang</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Invoice</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Supplier</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Cabang</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Total</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Dibayar</th>
                    <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Sisa</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($debts as $d)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $d->invoice_no }}</td>
                        <td class="px-4 py-3">{{ $d->supplier->name }}</td>
                        <td class="px-4 py-3">{{ $d->branch->name }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->total_amount) }}</td>
                        <td class="px-4 py-3 text-right">{{ format_currency($d->paidAmount()) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ format_currency($d->remainingAmount()) }}</td>
                        <td class="px-4 py-3">
                            @if($d->payment_status == 'partial')<x-badge variant="warning">Sebagian</x-badge>
                            @else<x-badge variant="danger">Belum</x-badge>@endif
                        </td>
                        <td class="px-4 py-3"><a href="{{ route('app.purchases.show', $d) }}"><x-button variant="secondary" size="sm">Detail</x-button></a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Semua utang sudah lunas. ✅</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($debts->hasPages())<div class="mt-4">{{ $debts->links() }}</div>@endif
</x-card>
@endsection
