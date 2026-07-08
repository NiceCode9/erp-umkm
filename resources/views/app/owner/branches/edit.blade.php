@extends('app.layouts.app')
@section('title', 'Edit Cabang')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Edit Cabang</h2>
        <form action="{{ route('app.branches.update', $branch) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <x-input label="Nama Cabang" name="name" type="text" value="{{ old('name', $branch->name) }}" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Alamat</label>
                    <textarea name="address" rows="3" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring">{{ old('address', $branch->address) }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-border text-primary focus:ring-ring" @checked($branch->is_active)>
                        <span class="text-sm text-foreground">Cabang aktif</span>
                    </label>
                </div>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Update</x-button>
                    <a href="{{ route('app.branches.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
