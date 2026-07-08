@extends('app.layouts.app')
@section('title', 'Edit Supplier')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Edit Supplier</h2>
        <form action="{{ route('app.suppliers.update', $supplier) }}" method="POST">
            @csrf @method('PUT')
            <div class="space-y-4">
                <x-input label="Nama Supplier" name="name" value="{{ old('name', $supplier->name) }}" required />
                <x-input label="Telepon" name="phone" value="{{ old('phone', $supplier->phone) }}" required />
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Alamat</label>
                    <textarea name="address" rows="3" class="block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring">{{ old('address', $supplier->address) }}</textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Update</x-button>
                    <a href="{{ route('app.suppliers.index') }}"><x-button variant="secondary" type="button">Batal</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
