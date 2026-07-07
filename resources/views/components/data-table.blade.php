@props([
    'headers' => [],
    'rows' => [],
    'emptyMessage' => 'Tidak ada data',
])

<div {{ $attributes->merge(['class' => 'w-full overflow-x-auto bg-card border border-border rounded-[var(--radius)] shadow-sm']) }}>
    <table class="w-full text-sm text-foreground">
        @if (count($headers) > 0)
            <thead class="bg-muted border-b border-border">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-muted-foreground">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-border">
            @forelse ($rows as $row)
                <tr class="hover:bg-muted/50 transition">
                    @foreach ($row as $cell)
                        <td class="px-4 py-3">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) > 0 ? count($headers) : 1 }}" class="px-4 py-8 text-center text-muted-foreground">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
