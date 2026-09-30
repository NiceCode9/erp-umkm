@extends('app.layouts.app')
@section('title', 'Tambah Bahan Baku')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Tambah Bahan Baku</h2>
        <form action="{{ route('app.raw-materials.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <x-input label="Nama Bahan Baku" name="name" value="{{ old('name') }}" required />
                <x-input label="Satuan (kg, liter, pcs, dll)" name="base_unit" value="{{ old('base_unit') }}" required />
                <x-input label="Stok Minimum (alert)" name="minimum_stock" type="number" step="0.01" value="{{ old('minimum_stock', 0) }}" />
                <div class="border-t border-border pt-4">
                    <h3 class="text-sm font-semibold text-foreground mb-3">Sertifikasi Halal (opsional)</h3>
                    <x-input label="Tanggal Kedaluwarsa Sertifikat" name="halal_cert_expired_date" type="date" value="{{ old('halal_cert_expired_date') }}" helperText="Jika sudah lewat tanggal ini, bahan baku tidak dapat dipakai untuk produksi." />
                </div>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Simpan</x-button>
                    <a href="{{ route('app.raw-materials.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
