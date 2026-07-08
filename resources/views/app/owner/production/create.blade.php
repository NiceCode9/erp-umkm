@extends('app.layouts.app')
@section('title', 'Produksi Baru')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Form Produksi Baru</h2>
        <form action="{{ route('app.production.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Produk <span class="text-destructive">*</span></label>
                    <select name="product_id" id="product-select" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>{{ $p->name }} ({{ $p->base_unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang <span class="text-destructive">*</span></label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring" required>
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id')==$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input label="Jumlah Target Produksi" name="quantity_target" type="number" step="0.01" min="0.01" value="{{ old('quantity_target') }}" required />
                <x-input label="Tanggal Kedaluwarsa (opsional)" name="expired_date" type="date" value="{{ old('expired_date') }}" helperText="Jika produk memiliki masa kedaluwarsa, isi tanggal ini. Akan tercatat di batch produk." />

                <div class="bg-muted rounded-[var(--radius)] p-4 text-sm text-muted-foreground">
                    <strong>Sistem akan:</strong>
                    <ol class="list-decimal ml-4 mt-1 space-y-0.5">
                        <li>Memeriksa ketersediaan stok bahan baku di cabang yang dipilih</li>
                        <li>Jika cukup, mengurangi stok batch per batch (FEFO — expired terdekat duluan)</li>
                        <li>Mencatat setiap pengurangan per batch di production_consumptions</li>
                        <li>Menambahkan stok produk jadi di cabang yang sama</li>
                    </ol>
                </div>

                <div class="flex gap-3 pt-2">
                    <x-button type="submit" id="btn-submit">Jalankan Produksi</x-button>
                    <a href="{{ route('app.production.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
