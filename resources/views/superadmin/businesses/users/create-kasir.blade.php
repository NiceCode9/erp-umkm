@extends('superadmin.layouts.app')
@section('title', 'Tambah Kasir - ' . $business->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <div class="mb-4">
            <a href="{{ route('superadmin.businesses.show', $business) }}" class="text-sm text-secondary hover:text-secondary/80">← Kembali ke {{ $business->name }}</a>
        </div>
        <h2 class="text-lg font-semibold mb-4">Tambah Kasir</h2>
        <form action="{{ route('superadmin.businesses.kasir.store', $business) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <x-input label="Nama Kasir" name="name" value="{{ old('name') }}" required />
                <x-input label="Email (username login)" name="email" type="email" value="{{ old('email') }}" required />
                <x-input label="Password" name="password" type="password" required />
                <x-input label="Konfirmasi Password" name="password_confirmation" type="password" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang <span class="text-destructive">*</span></label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring" required>
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<p class="text-sm text-destructive mt-1">{{ $message }}</p>@enderror
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
