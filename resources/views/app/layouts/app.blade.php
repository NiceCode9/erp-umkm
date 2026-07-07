<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    <meta name="theme-color" content="#58CC02">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-card border-r border-border hidden lg:block">
            <div class="p-4 border-b border-border">
                <h2 class="text-xl font-bold text-primary">{{ config('app.name') }}</h2>
                <p class="text-sm text-muted-foreground">{{ auth()->user()->business->name ?? '' }}</p>
            </div>
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('app.dashboard') }}" class="flex items-center px-4 py-2 rounded-[var(--radius)] {{ request()->routeIs('app.dashboard') ? 'bg-primary text-primary-foreground' : 'hover:bg-muted' }}">
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="bg-card border-b border-border px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">@yield('title')</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-muted-foreground">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 border border-border rounded-[var(--radius)] hover:bg-muted">
                                Logout
                            </button>
                        </form>
                    </div>
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
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
    @stack('scripts')
</body>
</html>
