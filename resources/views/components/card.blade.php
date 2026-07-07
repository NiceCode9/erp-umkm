@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-card text-card-foreground rounded-[var(--radius)] border border-border shadow-sm' . ($padding ? ' p-6' : '')]) }}>
    {{ $slot }}
</div>
