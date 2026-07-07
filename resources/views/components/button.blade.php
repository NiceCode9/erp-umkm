@props([
    'variant' => 'primary',
    'size' => 'default',
    'disabled' => false,
    'type' => 'button',
])

@php
$baseClasses = 'inline-flex items-center justify-center font-semibold rounded-[var(--radius)] focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 transition ease-in-out duration-150';

$variantClasses = match ($variant) {
    'primary' => 'bg-primary text-primary-foreground hover:bg-primary/90 border border-transparent',
    'secondary' => 'border border-border text-foreground hover:bg-muted bg-transparent',
    'danger' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90 border border-transparent',
    'ghost' => 'text-foreground hover:bg-muted bg-transparent border border-transparent',
    default => 'bg-primary text-primary-foreground hover:bg-primary/90 border border-transparent',
};

$sizeClasses = match ($size) {
    'sm' => 'px-3 py-1.5 text-sm',
    'lg' => 'px-6 py-3 text-lg',
    'default' => 'px-4 py-2 text-sm',
    default => 'px-4 py-2 text-sm',
};

$classes = trim("$baseClasses $variantClasses $sizeClasses");
@endphp

<button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
