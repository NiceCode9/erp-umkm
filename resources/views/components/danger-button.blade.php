<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground border border-transparent rounded-[var(--radius)] font-semibold text-xs uppercase tracking-widest hover:bg-destructive/90 active:bg-destructive/80 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
