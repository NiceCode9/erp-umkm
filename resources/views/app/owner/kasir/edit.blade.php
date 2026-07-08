@extends('app.layouts.app')
@section('title', 'Edit Kasir')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Edit Kasir</h2>
        <form action="{{ route('app.kasir.update', $kasir) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <x-input label="Nama Kasir" name="name" type="text" value="{{ old('name', $kasir->name) }}" required />
                <x-input label="Email (username login)" name="email" type="email" value="{{ old('email', $kasir->email) }}" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Cabang <span class="text-destructive">*</span></label>
                    <select name="branch_id" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring" required>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $kasir->branch_id) == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-border text-primary focus:ring-ring" @checked($kasir->is_active)>
                        <span class="text-sm text-foreground">Akun aktif</span>
                    </label>
                </div>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Update</x-button>
                    <a href="{{ route('app.kasir.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
