@extends('app.layouts.app')
@section('title', 'Tambah Kasir')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Tambah Kasir Baru</h2>
        <form action="{{ route('app.kasir.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <x-input label="Nama Kasir" name="name" type="text" value="{{ old('name') }}" required />
                <x-input label="Email (username login)" name="email" type="email" value="{{ old('email') }}" required />
                <x-input label="Password" name="password" type="password" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang <span class="text-destructive">*</span></label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring" required>
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Simpan</x-button>
                    <a href="{{ route('app.kasir.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
