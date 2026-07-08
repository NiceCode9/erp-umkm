@props(['disabled' => false, 'label' => false, 'error' => false, 'helperText' => false])

<div class="w-full">
    @if ($label)
        <label class="block text-sm font-medium text-foreground mb-1">{{ $label }}</label>
    @endif

    <input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full border border-input rounded-[var(--radius)] px-3 py-2 text-foreground bg-background focus:ring-2 focus:ring-ring focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed']) }}>

    @if ($error)
        <p class="mt-1 text-sm text-destructive">{{ $error }}</p>
    @endif

    @if ($helperText)
        <p class="mt-1 text-sm text-muted-foreground">{{ $helperText }}</p>
    @endif
</div>
