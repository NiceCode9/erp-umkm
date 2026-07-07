@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-input bg-background text-foreground focus:ring-ring focus:border-transparent rounded-[var(--radius)] shadow-sm']) }}>
