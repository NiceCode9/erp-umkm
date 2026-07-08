@extends('app.layouts.app')
@section('title', 'Customer')
@section('content')
<x-card>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Daftar Customer</h2>
        <a href="{{ route('app.customers.create') }}"><x-button>Tambah Customer</x-button></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Telepon</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Alamat</th>
                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($customers as $customer)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ $customer->name }}</td>
                        <td class="px-4 py-3">{{ $customer->phone }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $customer->address }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('app.customers.edit', $customer) }}"><x-button variant="secondary" size="sm">Edit</x-button></a>
                                <form action="{{ route('app.customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Hapus customer ini?')">@csrf @method('DELETE')<x-button variant="danger" size="sm" type="submit">Hapus</x-button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-muted-foreground">Belum ada customer.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($customers->hasPages())<div class="mt-4">{{ $customers->links() }}</div>@endif
</x-card>
@endsection
