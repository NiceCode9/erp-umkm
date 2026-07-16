@extends('app.layouts.app')
@section('title', 'Buat Sesi Opname')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Buat Sesi Stok Opname</h2>
        <p class="text-sm text-muted-foreground mb-6">Pilih cabang dan tipe item untuk membuat sesi opname. Data bisa diisi bertahap (draft) dan dikunci setelah selesai.</p>
        <form action="{{ route('app.stock-opnames.session.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang <span class="text-destructive">*</span></label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" required>
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Tipe Item <span class="text-destructive">*</span></label>
                    <select name="item_type" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 bg-background" required>
                        <option value="">-- Pilih --</option>
                        <option value="raw_material">Bahan Baku</option>
                        <option value="product">Produk Jadi</option>
                    </select>
                </div>
                <x-input label="Judul (opsional)" name="title" value="{{ old('title') }}" placeholder="Mis: Opname Bulan Juli 2026" />
                <x-input label="Tanggal Opname" name="opname_date" type="date" value="{{ old('opname_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required />
                <x-button type="submit">Buat Sesi Opname</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
