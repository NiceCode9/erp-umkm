@extends('superadmin.layouts.app')

@section('title', 'Edit Business')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold text-foreground mb-4">Form Edit Business</h2>
        <p class="text-sm text-muted-foreground mb-6">Edit data business. Untuk perubahan akun Owner (email/password) gunakan menu terpisah.</p>

        <form action="{{ route('superadmin.businesses.update', $business) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-foreground mb-1">Nama UMKM <span class="text-destructive">*</span></label>
                    <input type="text" id="name" name="name" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('name') border-destructive @enderror" value="{{ old('name', $business->name) }}" required>
                    @error('name')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="owner_name" class="block text-sm font-medium text-foreground mb-1">Nama Pemilik <span class="text-destructive">*</span></label>
                    <input type="text" id="owner_name" name="owner_name" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('owner_name') border-destructive @enderror" value="{{ old('owner_name', $business->owner_name) }}" required>
                    @error('owner_name')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-foreground mb-1">Telepon <span class="text-destructive">*</span></label>
                    <input type="text" id="phone" name="phone" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('phone') border-destructive @enderror" value="{{ old('phone', $business->phone) }}" required>
                    @error('phone')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-foreground mb-1">Alamat <span class="text-destructive">*</span></label>
                    <textarea id="address" name="address" rows="3" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent @error('address') border-destructive @enderror" required>{{ old('address', $business->address) }}</textarea>
                    @error('address')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button type="submit">Update</x-button>
                    <a href="{{ route('superadmin.businesses.index') }}">
                        <x-button variant="secondary" type="button">Batal</x-button>
                    </a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
