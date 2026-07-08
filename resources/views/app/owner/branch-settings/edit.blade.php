@extends('app.layouts.app')
@section('title', 'Setting Pajak - ' . $branch->name)
@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <h2 class="text-lg font-semibold mb-2">Setting Pajak Cabang</h2>
        <p class="text-sm text-muted-foreground mb-6">Cabang: <strong>{{ $branch->name }}</strong></p>

        <form action="{{ route('app.branches.settings.update', $branch) }}" method="POST">
            @csrf @method('PUT')
            <div class="space-y-6">
                <div x-data="{ enabled: {{ old('tax_enabled', $setting->tax_enabled) ? 'true' : 'false' }} }">
                    <label class="flex items-center gap-3 cursor-pointer mb-4">
                        <input type="checkbox" name="tax_enabled" value="1" x-model="enabled" class="rounded border-border text-primary focus:ring-ring" @checked($setting->tax_enabled)>
                        <div>
                            <span class="text-sm font-medium text-foreground">Aktifkan Pajak (PPN)</span>
                            <p class="text-xs text-muted-foreground">Jika diaktifkan, seluruh transaksi penjualan di cabang ini akan dikenakan pajak.</p>
                        </div>
                    </label>

                    <div x-show="enabled" x-cloak class="pl-8">
                        <x-input label="Persentase Pajak (%)" name="tax_percentage" type="number" step="0.01" min="0" max="100" value="{{ old('tax_percentage', $setting->tax_percentage) }}" />
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <x-button type="submit">Simpan Pengaturan</x-button>
                    <a href="{{ route('app.branches.index') }}"><x-button variant="secondary" type="button">Kembali</x-button></a>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
