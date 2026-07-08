@extends('app.layouts.app')
@section('title', 'Tambah Cabang')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Tambah Cabang Baru</h2>
        <form action="{{ route('app.branches.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <x-input label="Nama Cabang" name="name" type="text" value="{{ old('name') }}" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Alamat</label>
                    <textarea name="address" rows="3" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring">{{ old('address') }}</textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Simpan</x-button>
                    <a href="{{ route('app.branches.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
