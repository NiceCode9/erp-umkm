@props(['variant' => 'default'])

@php
$classes = match ($variant) {
    'success' => 'bg-primary/10 text-primary',
    'warning' => 'bg-warning/10 text-warning',
    'danger' => 'bg-destructive/10 text-destructive',
    'info' => 'bg-secondary/10 text-secondary',
    'default' => 'bg-muted text-muted-foreground',
    default => 'bg-muted text-muted-foreground',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $classes"]) }}>
    {{ $slot }}
</span>
