@extends('superadmin.layouts.app')
@section('title', 'Tambah Owner - ' . $business->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <div class="mb-4">
            <a href="{{ route('superadmin.businesses.show', $business) }}" class="text-sm text-secondary hover:text-secondary/80">← Kembali ke {{ $business->name }}</a>
        </div>
        <h2 class="text-lg font-semibold mb-4">Tambah Owner Tambahan</h2>
        <form action="{{ route('superadmin.businesses.owners.store', $business) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <x-input label="Nama" name="name" value="{{ old('name') }}" required />
                <x-input label="Email (username login)" name="email" type="email" value="{{ old('email') }}" required />
                <x-input label="Password" name="password" type="password" required />
                <x-input label="Konfirmasi Password" name="password_confirmation" type="password" required />
                <div class="bg-muted rounded-[var(--radius)] p-3 text-xs text-muted-foreground">
                    Role <strong>Owner</strong> akan di-assign otomatis. User ini akan punya akses ke seluruh data business ini.
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
