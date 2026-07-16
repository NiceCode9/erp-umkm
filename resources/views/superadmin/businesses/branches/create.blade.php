@extends('superadmin.layouts.app')
@section('title', 'Tambah Cabang - ' . $business->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <div class="mb-4">
            <a href="{{ route('superadmin.businesses.show', $business) }}" class="text-sm text-secondary hover:text-secondary/80">← Kembali ke {{ $business->name }}</a>
        </div>
        <h2 class="text-lg font-semibold mb-4">Tambah Cabang Baru</h2>
        <form action="{{ route('superadmin.businesses.branches.store', $business) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <x-input label="Nama Cabang" name="name" value="{{ old('name') }}" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Alamat</label>
                    <textarea name="address" rows="3" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring">{{ old('address') }}</textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Simpan</x-button>
                    <a href="{{ route('superadmin.businesses.show', $business) }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
