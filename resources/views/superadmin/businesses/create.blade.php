@extends('superadmin.layouts.app')

@section('title', 'Tambah Business')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold text-foreground mb-4">Form Tambah Business & Akun Owner</h2>
        <p class="text-sm text-muted-foreground mb-6">Satu form ini akan membuat Business (tenant) baru sekaligus akun Owner pertamanya.</p>

        <form action="{{ route('superadmin.businesses.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <h3 class="text-md font-medium text-foreground mb-3 pb-2 border-b border-border">Data Business</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-foreground mb-1">Nama UMKM <span class="text-destructive">*</span></label>
                            <input type="text" id="name" name="name" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('name') border-destructive @enderror" value="{{ old('name') }}" required>
                            @error('name')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="owner_name" class="block text-sm font-medium text-foreground mb-1">Nama Pemilik <span class="text-destructive">*</span></label>
                            <input type="text" id="owner_name" name="owner_name" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('owner_name') border-destructive @enderror" value="{{ old('owner_name') }}" required>
                            @error('owner_name')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-foreground mb-1">Telepon <span class="text-destructive">*</span></label>
                            <input type="text" id="phone" name="phone" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('phone') border-destructive @enderror" value="{{ old('phone') }}" required>
                            @error('phone')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-foreground mb-1">Alamat <span class="text-destructive">*</span></label>
                            <textarea id="address" name="address" rows="3" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('address') border-destructive @enderror" required>{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-md font-medium text-foreground mb-3 pb-2 border-b border-border">Akun Owner</h3>
                    <p class="text-xs text-muted-foreground mb-3">Email ini akan menjadi username login untuk Owner business ini.</p>
                    <div class="space-y-4">
                        <div>
                            <label for="owner_email" class="block text-sm font-medium text-foreground mb-1">Email Owner <span class="text-destructive">*</span></label>
                            <input type="email" id="owner_email" name="owner_email" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('owner_email') border-destructive @enderror" value="{{ old('owner_email') }}" required>
                            @error('owner_email')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="owner_password" class="block text-sm font-medium text-foreground mb-1">Password Owner <span class="text-destructive">*</span></label>
                            <input type="password" id="owner_password" name="owner_password" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('owner_password') border-destructive @enderror" required>
                            @error('owner_password')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="owner_password_confirmation" class="block text-sm font-medium text-foreground mb-1">Konfirmasi Password Owner <span class="text-destructive">*</span></label>
                            <input type="password" id="owner_password_confirmation" name="owner_password_confirmation" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent" required>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button type="submit">Simpan</x-button>
                    <a href="{{ route('superadmin.businesses.index') }}">
                        <x-button variant="secondary" type="button">Batal</x-button>
                    </a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
