<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

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
                <ul class="space-y-1">
                    @role('Owner')
                        <li><a href="{{ route('app.dashboard') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.dashboard') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Dashboard</a>
                        </li>
                        <li><a href="{{ route('app.branches.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.branches.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Cabang</a>
                        </li>
                        <li><a href="{{ route('app.kasir.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.kasir.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Kasir</a>
                        </li>
                        <li><a href="{{ route('app.raw-materials.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.raw-materials.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Bahan
                                Baku</a></li>
                        <li><a href="{{ route('app.products.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.products.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Produk</a>
                        </li>
                        <li><a href="{{ route('app.suppliers.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.suppliers.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Supplier</a>
                        </li>
                        <li><a href="{{ route('app.customers.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.customers.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Customer</a>
                        </li>
                        <li class="pt-2 mt-2 border-t border-border"><a href="{{ route('app.purchases.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.purchases.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Pembelian</a>
                        </li>
                        <li><a href="{{ route('app.debts.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.debts.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Utang
                                Supplier</a></li>
                        <li><a href="{{ route('app.production.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.production.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Produksi</a>
                        </li>
                        <li><a href="{{ route('app.stock-movements.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.stock-movements.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Riwayat
                                Stok</a></li>
                        {{-- <li><a href="{{ route('app.stock-movements.index') }}" class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.stock-movements.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Riwayat Stok</a></li> --}}
                        <li class="pt-2 mt-2 border-t border-border"><a href="{{ route('app.owner.sales.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.owner.sales.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Penjualan</a>
                        </li>
                        <li><a href="{{ route('app.owner.shifts.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.owner.shifts.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Rekap Shift</a>
                        </li>
                        <li><a href="{{ route('app.owner.receivables.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.owner.receivables.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Piutang</a>
                        </li>
                        <li><a href="{{ route('app.owner.shipments.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.owner.shipments.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Pengiriman</a>
                        </li>
                        @elserole('Kasir')
                        <li><a href="{{ route('app.dashboard') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.dashboard') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Dashboard</a>
                        </li>
                        <li><a href="{{ route('app.kasir.pos') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.kasir.pos') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Kasir
                                (POS)</a></li>
                        <li><a href="{{ route('app.kasir.sales.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.kasir.sales.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Riwayat
                                Penjualan</a></li>
                        <li><a href="{{ route('app.kasir.receivables.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.kasir.receivables.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Piutang</a>
                        </li>
                        <li><a href="{{ route('app.kasir.shipments.index') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.kasir.shipments.*') ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted' }}">Pengiriman</a>
                        </li>
                        <li class="pt-2 mt-2 border-t border-border"><a href="{{ route('app.kasir.shifts.close') }}"
                                class="flex items-center px-4 py-2 rounded-[var(--radius)] text-sm {{ request()->routeIs('app.kasir.shifts.close') ? 'bg-destructive text-destructive-foreground' : 'text-destructive hover:bg-destructive/10' }}">Tutup Shift</a></li>
                    @endrole
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
                            <button type="submit"
                                class="px-4 py-2 border border-border rounded-[var(--radius)] hover:bg-muted">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if (session('success'))
                    <div class="mb-4 p-4 bg-primary/10 text-primary rounded-[var(--radius)] border border-primary/20">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div
                        class="mb-4 p-4 bg-destructive/10 text-destructive rounded-[var(--radius)] border border-destructive/20">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
