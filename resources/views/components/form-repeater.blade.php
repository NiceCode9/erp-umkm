@props([
    'name' => 'items',
    'label' => null,
    'addLabel' => '+ Tambah Baris',
    'emptyMessage' => 'Belum ada data.',
    'minItems' => 1,
])

<div x-data="{
    items: [{}],
    initItems(count) {
        this.items = [];
        for (let i = 0; i < count; i++) this.items.push({});
    },
    addItem() { this.items.push({}); },
    removeItem(index) {
        if (this.items.length > {{ $minItems }}) this.items.splice(index, 1);
    }
}" {{ $attributes }}>
    @if ($label)
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-foreground">{{ $label }}</label>
            <button type="button" @click="addItem()" class="text-sm text-secondary hover:text-secondary/80">{{ $addLabel }}</button>
        </div>
    @endif

    <template x-for="(item, index) in items" :key="index">
        <div class="p-4 bg-muted rounded-[var(--radius)] border border-border mb-2 relative">
            <button type="button" @click="removeItem(index)" x-show="items.length > {{ $minItems }}" class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-destructive text-destructive-foreground text-xs flex items-center justify-center hover:bg-destructive/90" title="Hapus">&times;</button>
            <div class="grid grid-cols-1 md:grid-cols-{{ $slot ? '4' : '1' }} gap-3">
                {{ $slot }}
            </div>
        </div>
    </template>

    @if ($emptyMessage)
        <p x-show="items.length === 0" class="text-sm text-muted-foreground py-4 text-center">{{ $emptyMessage }}</p>
    @endif

    <button type="button" @click="addItem()" class="text-sm text-secondary hover:text-secondary/80 mt-1">{{ $addLabel }}</button>
</div>
