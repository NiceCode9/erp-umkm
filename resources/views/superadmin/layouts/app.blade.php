<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Superadmin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-card border-r border-border hidden lg:block">
            <div class="p-4 border-b border-border">
                <h2 class="text-xl font-bold text-primary">{{ config('app.name') }}</h2>
                <p class="text-sm text-muted-foreground">Superadmin Panel</p>
            </div>
            <nav class="p-4">
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('superadmin.dashboard') }}" class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('superadmin.dashboard') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('superadmin.businesses.index') }}" class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('superadmin.businesses.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Kelola Business
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="bg-card border-b border-border px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" class="lg:hidden p-2 rounded-[var(--radius)] hover:bg-muted" onclick="document.querySelector('aside').classList.toggle('hidden')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-xl font-semibold">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-muted-foreground">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button variant="secondary" size="sm" type="submit">Logout</x-button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-primary/10 text-primary rounded-[var(--radius)] border border-primary/20">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-4 bg-destructive/10 text-destructive rounded-[var(--radius)] border border-destructive/20">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>

            <footer class="border-t border-border px-6 py-3 text-sm text-muted-foreground">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
