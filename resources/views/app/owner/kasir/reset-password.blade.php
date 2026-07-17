@extends('app.layouts.app')

@section('title', 'Reset Password Kasir')

@section('content')
<div class="max-w-md mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Reset Password Kasir</h2>
        <p class="text-sm text-muted-foreground mb-6">Mengatur ulang password untuk kasir <strong>{{ $kasir->name }}</strong> ({{ $kasir->email }})</p>

        <form action="{{ route('app.kasir.reset-password', $kasir) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-foreground mb-1">Password Baru</label>
                    <input type="password" name="password" id="password" required minlength="8"
                        class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent"
                        autocomplete="new-password">
                    @error('password')
                        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                        class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent"
                        autocomplete="new-password">
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Simpan Password Baru</x-button>
                    <a href="{{ route('app.kasir.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection