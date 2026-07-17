@php
    // Ganti nilai ini sekali saja — dipakai di seluruh halaman (judul tab, navbar, footer, dsb)
    $brand = 'ERP Halal';
    $tagline = 'ERP UMKM Halal';
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }} — Sistem ERP untuk UMKM Bersertifikat Halal</title>
    <meta name="description" content="Kelola stok, kasir, produksi, dan status sertifikasi halal produk UMKM Anda dalam satu sistem.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page text-ink antialiased">

    {{-- ============ NAVBAR ============ --}}
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b-2 border-border">
        <nav class="max-w-6xl mx-auto px-5 h-[72px] flex items-center justify-between">
            <a href="#" class="flex items-center gap-2 focus-ring">
                <svg width="36" height="36" viewBox="0 0 40 40" fill="none">
                    <circle cx="20" cy="20" r="19" fill="#58CC02" stroke="#58A700" stroke-width="2"/>
                    <path d="M13 20.5L17.5 25L27 14.5" stroke="white" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="font-display font-extrabold text-xl">{{ $brand }}</span>
            </a>

            <div class="hidden md:flex items-center gap-8 font-bold text-[15px] text-ink">
                <a href="#fitur" class="hover:text-primary focus-ring">Fitur</a>
                <a href="#halal" class="hover:text-primary focus-ring">Sertifikasi Halal</a>
                <a href="#cara-kerja" class="hover:text-primary focus-ring">Cara Kerja</a>
                <a href="#paket" class="hover:text-primary focus-ring">Paket</a>
                <a href="#faq" class="hover:text-primary focus-ring">FAQ</a>
            </div>

            <div class="hidden md:flex items-center gap-3">
                {{-- Ganti "#" dengan route('login') sesuai project Laravel Anda --}}
                <a href="{{ route('login') }}" class="font-extrabold text-[15px] text-secondary hover:text-secondary-shadow px-3 focus-ring">Masuk</a>
                <a href="#kontak" class="press-btn focus-ring inline-flex items-center justify-center bg-primary hover:bg-primary-hover text-white font-extrabold text-[15px] px-5 h-[46px] rounded-[12px] shadow-btn">
                    Minta Demo
                </a>
            </div>

            <button id="menuBtn" class="md:hidden w-10 h-10 grid place-items-center focus-ring" aria-label="Buka menu">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16" stroke="#3C3C3C" stroke-width="2.4" stroke-linecap="round"/></svg>
            </button>
        </nav>

        <div id="mobileMenu" class="hidden md:hidden border-t-2 border-border bg-white px-5 py-4 flex flex-col gap-4 font-bold">
            <a href="#fitur" class="focus-ring">Fitur</a>
            <a href="#halal" class="focus-ring">Sertifikasi Halal</a>
            <a href="#cara-kerja" class="focus-ring">Cara Kerja</a>
            <a href="#paket" class="focus-ring">Paket</a>
            <a href="#faq" class="focus-ring">FAQ</a>
            <a href="#kontak" class="press-btn focus-ring text-center bg-primary text-white px-5 h-[46px] leading-[46px] rounded-[12px] shadow-btn">Minta Demo</a>
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden bg-surface-1 border-b-2 border-border">
        {{-- Ambient gradient blobs, animated drift — bukan gradient statis generik --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="blob blob-a absolute w-[420px] h-[420px] rounded-full bg-primary/10 blur-3xl"></div>
            <div class="blob blob-b absolute w-[360px] h-[360px] rounded-full bg-secondary/10 blur-3xl"></div>
        </div>

        <div class="relative max-w-6xl mx-auto px-5 pt-16 pb-20 md:pt-20 md:pb-28 grid md:grid-cols-2 gap-14 items-center">
            <div class="reveal">
                <div class="inline-flex items-center gap-2 bg-white border-2 border-border rounded-full px-4 py-1.5 mb-6 shadow-card">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                    <span class="font-extrabold text-sm text-ink-muted">ERP UMKM bersertifikat halal, multi-cabang</span>
                </div>

                <h1 class="font-display font-extrabold text-[40px] md:text-[52px] leading-[1.1] tracking-[-0.01em] text-ink">
                    Jualan jalan terus,<br>
                    <span class="text-primary">sertifikat halal</span> nggak pernah kelewat.
                </h1>

                <p class="mt-5 text-lg text-ink-muted font-bold leading-relaxed max-w-md">
                    {{ $brand }} menyatukan stok, kasir, produksi, dan status sertifikasi halal produk Anda di semua cabang — supaya usaha rapi dan pelanggan makin percaya.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a href="#kontak" class="press-btn focus-ring inline-flex items-center justify-center bg-primary hover:bg-primary-hover text-white font-extrabold text-base px-7 h-[56px] rounded-[12px] shadow-btn">
                        Minta Demo
                    </a>
                    <a href="#cara-kerja" class="press-btn focus-ring inline-flex items-center justify-center bg-white text-secondary font-extrabold text-base px-7 h-[56px] rounded-[12px] border-2 border-border">
                        Lihat Cara Kerja
                    </a>
                </div>

                {{-- Chip kapabilitas — jujur (bukan klaim angka pengguna), tapi tetap scannable --}}
                <div class="mt-10 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 bg-white border-2 border-border rounded-full pl-2 pr-3.5 py-1.5 text-sm font-extrabold text-ink">
                        <span class="w-6 h-6 rounded-full bg-primary/15 grid place-items-center text-primary text-xs">✓</span> Multi-Cabang
                    </span>
                    <span class="inline-flex items-center gap-1.5 bg-white border-2 border-border rounded-full pl-2 pr-3.5 py-1.5 text-sm font-extrabold text-ink">
                        <span class="w-6 h-6 rounded-full bg-secondary/15 grid place-items-center text-secondary text-xs">✓</span> Produksi FEFO Otomatis
                    </span>
                    <span class="inline-flex items-center gap-1.5 bg-white border-2 border-border rounded-full pl-2 pr-3.5 py-1.5 text-sm font-extrabold text-ink">
                        <span class="w-6 h-6 rounded-full bg-streak/15 grid place-items-center text-streak text-xs">✓</span> Reminder 30 Hari
                    </span>
                </div>
            </div>

            {{-- Hero mockup: kartu produk + kartu cabang + stempel halal, dengan tilt mengikuti mouse --}}
            <div class="reveal relative" id="heroTiltWrap">
                <div id="heroCard" class="relative bg-white border-2 border-border rounded-[28px] shadow-elevated p-6 max-w-sm mx-auto transition-transform duration-200 ease-out will-change-transform">
                    <div class="flex items-center justify-between mb-4">
                        <p class="font-display font-extrabold text-lg">Keripik Talas Bu Sri</p>
                        <span class="text-xs font-extrabold text-ink-muted bg-surface-2 px-2.5 py-1 rounded-full">SKU-0421</span>
                    </div>

                    <div class="h-36 rounded-[12px] bg-gradient-to-br from-surface-2 to-surface-1 border-2 border-border mb-4 grid place-items-center">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none"><path d="M4 13l3-7h10l3 7M4 13v6a1 1 0 001 1h14a1 1 0 001-1v-6M4 13h16" stroke="#B9B9B9" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>

                    <div class="flex items-center justify-between text-sm font-bold text-ink-muted mb-1">
                        <span>Stok tersedia · Cabang Kebon Jeruk</span><span class="text-ink">184 pcs</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-surface-2 overflow-hidden mb-5">
                        <div class="h-full rounded-full bg-primary fill-anim" style="width:72%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-extrabold text-ink-muted uppercase tracking-wide">Status Sertifikat Halal</p>
                            <p class="font-extrabold text-primary">Terverifikasi &middot; berlaku s.d 2027</p>
                        </div>
                    </div>
                </div>

                {{-- floating halal seal stamp, dengan ink-ripple saat masuk viewport --}}
                <div class="stamp absolute -top-6 -right-4 md:-right-8 float">
                    <div class="stamp-ring absolute inset-0 rounded-full"></div>
                    <div class="w-24 h-24 rounded-full bg-white border-4 border-primary shadow-elevated grid place-items-center rotate-[-8deg] relative z-10">
                        <div class="text-center leading-tight">
                            <svg class="mx-auto" width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M4 12.5L9 17.5L20 6" stroke="#58CC02" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <p class="font-display font-extrabold text-[10px] text-primary tracking-wide mt-0.5">HALAL<br>VERIFIED</p>
                        </div>
                    </div>
                </div>

                <div class="absolute -bottom-5 -left-6 float-slow hidden sm:block">
                    <div class="bg-white border-2 border-border rounded-[20px] shadow-card px-4 py-3 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-streak/15 grid place-items-center">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2c1 4-3 5-3 9a3 3 0 006 0c0-1.5-1-2-1-2s2 1 2 4a5 5 0 01-10 0c0-5 4-6 4-11 1 0 1.5.5 2 0z" fill="#FF9600"/></svg>
                        </span>
                        <div>
                            <p class="font-extrabold text-xs text-ink-muted">Reminder otomatis</p>
                            <p class="font-extrabold text-sm text-ink">30 hari sebelum expired</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FEATURES ============ --}}
    <section id="fitur" class="max-w-6xl mx-auto px-5 py-20 md:py-28">
        <div class="text-center max-w-xl mx-auto mb-14 reveal">
            <p class="font-extrabold text-secondary uppercase tracking-wide text-sm mb-3">Satu sistem, semua cabang</p>
            <h2 class="font-display font-extrabold text-3xl md:text-4xl leading-tight">Fitur yang UMKM benar-benar pakai tiap hari</h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
                $features = [
                    ['color' => 'primary', 'title' => 'Manajemen Stok & FEFO', 'desc' => 'Stok bahan baku & produk jadi per cabang, otomatis pakai yang paling dekat kedaluwarsa duluan.', 'icon' => 'box'],
                    ['color' => 'secondary', 'title' => 'Kasir & Penjualan', 'desc' => 'Transaksi di kasir langsung tercatat ke laporan dan mengurangi stok, tanpa input dua kali.', 'icon' => 'cart'],
                    ['color' => 'brand-purple', 'title' => 'Pelacakan Sertifikat Halal', 'desc' => 'Nomor sertifikat, lembaga penerbit, dan masa berlaku tiap produk terpantau dalam satu dashboard.', 'icon' => 'seal'],
                    ['color' => 'streak', 'title' => 'Reminder Perpanjangan', 'desc' => 'Notifikasi otomatis 30 hari sebelum sertifikat halal habis masa berlaku — sebelum jadi masalah.', 'icon' => 'bell'],
                    ['color' => 'brand-red', 'title' => 'Laporan Keuangan', 'desc' => 'Penjualan, utang-piutang, dan stok tersaji per cabang maupun gabungan semua cabang.', 'icon' => 'chart'],
                    ['color' => 'brand-yellow', 'title' => 'Multi Cabang & Tim', 'desc' => 'Kelola banyak cabang dalam satu akun, atur akses Kasir sesuai cabang tempat dia bertugas.', 'icon' => 'store'],
                ];
                $iconPaths = [
                    'box' => '<path d="M3 8l9-5 9 5-9 5-9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
                    'cart' => '<circle cx="9" cy="20" r="1.4" fill="currentColor" stroke="none"/><circle cx="18" cy="20" r="1.4" fill="currentColor" stroke="none"/><path d="M3 4h2l2.4 12h11.2L21 8H6.2"/>',
                    'seal' => '<circle cx="12" cy="12" r="8.5"/><path d="M9 12l2 2 4-4.5"/>',
                    'bell' => '<path d="M6 9a6 6 0 0112 0c0 4 1.5 5.5 1.5 5.5H4.5S6 13 6 9z"/><path d="M10 19a2 2 0 004 0"/>',
                    'chart' => '<path d="M4 20V10M12 20V4M20 20v-7"/>',
                    'store' => '<path d="M4 9l1-5h14l1 5"/><path d="M4 9v10h16V9"/><path d="M9 19v-6h6v6"/>',
                ];
            @endphp

            @foreach ($features as $i => $f)
                <div class="reveal bg-white border-2 border-border rounded-[20px] p-6 shadow-card hover:-translate-y-1.5 hover:shadow-elevated hover:border-{{ $f['color'] }}/40 transition-all duration-300" style="transition-delay: {{ $i * 60 }}ms">
                    <div class="w-12 h-12 rounded-[12px] bg-{{ $f['color'] }}/15 grid place-items-center mb-4">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="text-{{ $f['color'] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            {!! $iconPaths[$f['icon']] !!}
                        </svg>
                    </div>
                    <h3 class="font-display font-extrabold text-lg mb-1.5">{{ $f['title'] }}</h3>
                    <p class="text-ink-muted font-bold text-[15px] leading-relaxed">{{ $f['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ HALAL JOURNEY (signature section) ============ --}}
    <section id="halal" class="bg-surface-1 border-y-2 border-border py-20 md:py-28">
        <div class="max-w-4xl mx-auto px-5">
            <div class="text-center max-w-xl mx-auto mb-16 reveal">
                <p class="font-extrabold text-primary uppercase tracking-wide text-sm mb-3">Kenapa {{ $brand }} beda</p>
                <h2 class="font-display font-extrabold text-3xl md:text-4xl leading-tight mb-4">Status sertifikasi halal, terpantau sampai mendekati expired</h2>
                <p class="text-ink-muted font-bold">Bukan cuma dicatat manual di spreadsheet — nomor sertifikat, lembaga penerbit, dan masa berlaku tiap produk terpantau sistem, dengan pengingat otomatis sebelum jatuh tempo.</p>
            </div>

            <div class="relative">
                <div class="hidden md:block absolute left-1/2 top-6 bottom-6 w-1 -translate-x-1/2 path-line"></div>

                @php
                    $steps = [
                        ['n' => '01', 'title' => 'Catat data sertifikat', 'desc' => 'Input nomor sertifikat, lembaga penerbit, dan tanggal kedaluwarsa langsung di data produk.', 'color' => 'secondary'],
                        ['n' => '02', 'title' => 'Terpantau otomatis', 'desc' => 'Dashboard menghitung mundur masa berlaku tiap produk, tidak perlu dicek manual satu per satu.', 'color' => 'brand-yellow'],
                        ['n' => '03', 'title' => 'Diingatkan 30 hari sebelumnya', 'desc' => 'Notifikasi muncul begitu sertifikat mendekati 30 hari sebelum expired.', 'color' => 'primary'],
                        ['n' => '04', 'title' => 'Sudah lewat? Ditandai tegas', 'desc' => 'Produk yang sertifikatnya sudah kedaluwarsa ditandai terpisah, supaya jadi prioritas Anda.', 'color' => 'streak'],
                    ];
                @endphp

                <div class="space-y-10 md:space-y-14">
                    @foreach ($steps as $i => $s)
                        <div class="reveal relative flex flex-col md:flex-row items-center gap-5 md:gap-10 {{ $i % 2 == 1 ? 'md:flex-row-reverse' : '' }}">
                            <div class="md:w-1/2 {{ $i % 2 == 1 ? 'md:text-left' : 'md:text-right' }} text-center">
                                <span class="font-display font-extrabold text-sm text-{{ $s['color'] }}">LANGKAH {{ $s['n'] }}</span>
                                <h3 class="font-display font-extrabold text-xl mt-1 mb-2">{{ $s['title'] }}</h3>
                                <p class="text-ink-muted font-bold text-[15px] max-w-sm {{ $i % 2 == 1 ? '' : 'md:ml-auto' }}">{{ $s['desc'] }}</p>
                            </div>
                            <div class="relative z-10 shrink-0 w-16 h-16 rounded-full bg-{{ $s['color'] }} border-4 border-white shadow-elevated grid place-items-center">
                                <span class="font-display font-extrabold text-white text-lg">{{ $s['n'] }}</span>
                            </div>
                            <div class="md:w-1/2 hidden md:block"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============ CARA KERJA (alur singkat) ============ --}}
    <section id="cara-kerja" class="max-w-6xl mx-auto px-5 py-20 md:py-28">
        <div class="text-center max-w-xl mx-auto mb-14 reveal">
            <p class="font-extrabold text-secondary uppercase tracking-wide text-sm mb-3">Alur kerja</p>
            <h2 class="font-display font-extrabold text-3xl md:text-4xl leading-tight">Dari bahan baku sampai laporan, satu alur</h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @php
                $flow = [
                    ['n' => '1', 'title' => 'Pembelian', 'desc' => 'Bahan baku masuk per batch, tanggal kedaluwarsa tercatat otomatis.'],
                    ['n' => '2', 'title' => 'Produksi', 'desc' => 'Pilih resep, sistem hitung kebutuhan bahan & kurangi stok otomatis (FEFO).'],
                    ['n' => '3', 'title' => 'Kasir', 'desc' => 'Transaksi jalan cepat, stok produk berkurang otomatis saat checkout.'],
                    ['n' => '4', 'title' => 'Laporan', 'desc' => 'Penjualan, stok, dan utang-piutang terangkum per cabang & gabungan.'],
                ];
            @endphp
            @foreach ($flow as $step)
                <div class="reveal bg-white border-2 border-border rounded-[20px] p-6 shadow-card">
                    <span class="font-display font-extrabold text-3xl text-primary">{{ $step['n'] }}</span>
                    <h3 class="font-display font-extrabold text-lg mt-2 mb-1.5">{{ $step['title'] }}</h3>
                    <p class="text-ink-muted font-bold text-[15px] leading-relaxed">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ COCOK UNTUK (pengganti testimoni fiktif) ============ --}}
    <section class="bg-surface-1 border-y-2 border-border py-20 md:py-28">
        <div class="max-w-6xl mx-auto px-5">
            <div class="text-center max-w-xl mx-auto mb-14 reveal">
                <p class="font-extrabold text-secondary uppercase tracking-wide text-sm mb-3">Cocok untuk usaha seperti</p>
                <h2 class="font-display font-extrabold text-3xl md:text-4xl leading-tight">Dibangun untuk UMKM produksi & multi-cabang</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @php
                    $usecases = [
                        ['title' => 'Bakery & Kue', 'desc' => 'Produksi harian dengan banyak resep skala berbeda, stok bahan baku mudah kedaluwarsa.'],
                        ['title' => 'Produsen Makanan Olahan', 'desc' => 'Keripik, sambal, dan produk kemasan yang wajib bersertifikat halal untuk dipasarkan luas.'],
                        ['title' => 'Katering', 'desc' => 'Pembelian bahan dalam jumlah besar, produksi terjadwal, pengiriman ke banyak pelanggan.'],
                        ['title' => 'Retail Multi-Cabang', 'desc' => 'Beberapa toko dalam satu bisnis, butuh laporan gabungan sekaligus per cabang.'],
                    ];
                @endphp
                @foreach ($usecases as $i => $u)
                    <div class="reveal bg-white border-2 border-border rounded-[20px] p-6 shadow-card" style="transition-delay: {{ $i * 60 }}ms">
                        <h3 class="font-display font-extrabold text-lg mb-1.5">{{ $u['title'] }}</h3>
                        <p class="text-ink-muted font-bold text-[15px] leading-relaxed">{{ $u['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PAKET (tanpa harga/self-checkout — onboarding via tim) ============ --}}
    <section id="paket" class="max-w-6xl mx-auto px-5 py-20 md:py-28">
        <div class="text-center max-w-xl mx-auto mb-14 reveal">
            <p class="font-extrabold text-secondary uppercase tracking-wide text-sm mb-3">Paket</p>
            <h2 class="font-display font-extrabold text-3xl md:text-4xl leading-tight">Skala usaha Anda, kami sesuaikan</h2>
            <p class="text-ink-muted font-bold mt-4">Tidak ada pendaftaran mandiri — tim kami bantu setup akun & cabang pertama Anda langsung, supaya konfigurasinya pas sejak awal.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @php
                $tiers = [
                    ['name' => 'Starter', 'desc' => 'Untuk usaha yang baru mulai rapi-rapi', 'points' => ['1 cabang', 'Kasir & manajemen stok', 'Pelacakan sertifikat halal'], 'highlight' => false],
                    ['name' => 'Growth', 'desc' => 'Untuk usaha yang mulai jualan di banyak tempat', 'points' => ['Beberapa cabang', 'Produksi & resep FEFO', 'Laporan keuangan lengkap'], 'highlight' => true],
                    ['name' => 'Scale', 'desc' => 'Untuk usaha dengan banyak cabang & tim', 'points' => ['Cabang & tim tanpa batas', 'Distribusi stok antar cabang', 'Dukungan prioritas'], 'highlight' => false],
                ];
            @endphp
            @foreach ($tiers as $t)
                <div class="reveal relative bg-white border-2 {{ $t['highlight'] ? 'border-primary md:-translate-y-3 shadow-elevated' : 'border-border shadow-card' }} rounded-[20px] p-8">
                    @if ($t['highlight'])
                        <span class="absolute -top-4 left-1/2 -translate-x-1/2 bg-brand-yellow text-ink font-display font-extrabold text-xs uppercase tracking-wide px-4 py-1.5 rounded-full shadow-btn-sm">Paling Umum Dipakai</span>
                    @endif
                    <h3 class="font-display font-extrabold text-xl mb-1">{{ $t['name'] }}</h3>
                    <p class="text-ink-muted font-bold text-sm mb-6">{{ $t['desc'] }}</p>
                    <ul class="space-y-3 mb-8 text-[15px] font-bold text-ink">
                        @foreach ($t['points'] as $point)
                            <li class="flex gap-2"><span class="text-primary">✓</span> {{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="#kontak" class="press-btn focus-ring block text-center {{ $t['highlight'] ? 'bg-primary hover:bg-primary-hover text-white shadow-btn' : 'bg-white text-secondary border-2 border-border' }} font-extrabold px-6 h-[52px] leading-[52px] rounded-[12px]">
                        Diskusikan Kebutuhan
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section id="faq" class="bg-surface-1 border-y-2 border-border py-20 md:py-28">
        <div class="max-w-2xl mx-auto px-5">
            <div class="text-center mb-12 reveal">
                <h2 class="font-display font-extrabold text-3xl md:text-4xl leading-tight">Pertanyaan yang sering ditanyakan</h2>
            </div>

            <div class="space-y-4">
                @php
                    $faqs = [
                        ['q' => 'Apakah '.$brand.' membantu mengurus sertifikasi halal ke BPJPH?', 'a' => 'Tidak — proses resmi sertifikasi tetap diajukan ke lembaga berwenang. '.$brand.' membantu Anda mencatat nomor sertifikat, lembaga penerbit, dan masa berlakunya, lalu mengingatkan sebelum masa berlaku habis, supaya tidak ada yang terlewat.'],
                        ['q' => 'Bagaimana cara mulai pakai '.$brand.'?', 'a' => 'Tidak ada pendaftaran mandiri. Hubungi kami lewat form di bawah, tim kami akan bantu buatkan akun bisnis dan cabang pertama Anda langsung, sesuai kebutuhan.'],
                        ['q' => 'Apakah data toko saya aman?', 'a' => 'Data tiap bisnis terisolasi satu sama lain di sistem kami — bisnis Anda tidak bisa diakses atau terlihat oleh tenant lain.'],
                        ['q' => 'Bisa dipakai untuk lebih dari satu cabang?', 'a' => 'Bisa. Anda bisa kelola banyak cabang dalam satu akun bisnis, dengan laporan yang bisa dilihat per cabang maupun gabungan semua cabang.'],
                        ['q' => 'Apa itu FEFO yang disebut-sebut di fitur produksi?', 'a' => 'FEFO (First Expired First Out) berarti bahan baku yang paling dekat kedaluwarsanya dipakai lebih dulu saat produksi — sistem yang menghitungnya otomatis, Anda tidak perlu cek manual per batch.'],
                    ];
                @endphp

                @foreach ($faqs as $f)
                    <details class="reveal group bg-white border-2 border-border rounded-[20px] overflow-hidden">
                        <summary class="focus-ring cursor-pointer px-6 py-5 flex items-center justify-between gap-4 font-display font-extrabold text-[17px]">
                            {{ $f['q'] }}
                            <svg class="chev shrink-0 transition-transform duration-200" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="#777" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        <div class="px-6 pb-5 text-ink-muted font-bold text-[15px] leading-relaxed">{{ $f['a'] }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ FINAL CTA / KONTAK ============ --}}
    <section id="kontak" class="max-w-6xl mx-auto px-5 py-20 md:py-24">
        <div class="reveal relative bg-primary rounded-[28px] px-8 py-16 md:py-20 text-center overflow-hidden shadow-btn">
            <svg class="absolute -top-8 -left-8 opacity-20" width="140" height="140" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.4"/></svg>
            <svg class="absolute -bottom-10 -right-10 opacity-20" width="180" height="180" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.4"/></svg>

            <h2 class="font-display font-extrabold text-3xl md:text-[40px] text-white leading-tight max-w-2xl mx-auto mb-4">
                Rapikan usaha halal Anda, mulai dari demo singkat
            </h2>
            <p class="text-white/90 font-bold mb-9 max-w-md mx-auto">Tim kami bantu setup akun bisnis, cabang, dan tim Anda — tanpa perlu daftar sendiri.</p>
            {{-- Ganti "#" dengan mailto:, link WhatsApp, atau route ke form kontak Anda --}}
            <a href="#" class="press-btn focus-ring inline-flex items-center justify-center bg-white text-primary font-extrabold text-base px-8 h-[56px] rounded-[12px] shadow-[0_4px_0_#EBEBEB]">
                Minta Demo Sekarang
            </a>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="border-t-2 border-border">
        <div class="max-w-6xl mx-auto px-5 py-14 grid sm:grid-cols-2 md:grid-cols-4 gap-10">
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-2 mb-3">
                    <svg width="30" height="30" viewBox="0 0 40 40" fill="none">
                        <circle cx="20" cy="20" r="19" fill="#58CC02" stroke="#58A700" stroke-width="2"/>
                        <path d="M13 20.5L17.5 25L27 14.5" stroke="white" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="font-display font-extrabold text-lg">{{ $brand }}</span>
                </div>
                <p class="text-ink-muted font-bold text-sm leading-relaxed">{{ $tagline }} untuk usaha kecil dan menengah di Indonesia.</p>
            </div>
            <div>
                <p class="font-display font-extrabold text-sm mb-4">Produk</p>
                <ul class="space-y-3 text-sm font-bold text-ink-muted">
                    <li><a href="#fitur" class="hover:text-primary focus-ring">Fitur</a></li>
                    <li><a href="#halal" class="hover:text-primary focus-ring">Sertifikasi Halal</a></li>
                    <li><a href="#paket" class="hover:text-primary focus-ring">Paket</a></li>
                </ul>
            </div>
            <div>
                <p class="font-display font-extrabold text-sm mb-4">Perusahaan</p>
                <ul class="space-y-3 text-sm font-bold text-ink-muted">
                    <li><a href="#" class="hover:text-primary focus-ring">Tentang Kami</a></li>
                    <li><a href="#faq" class="hover:text-primary focus-ring">FAQ</a></li>
                    <li><a href="#kontak" class="hover:text-primary focus-ring">Kontak</a></li>
                </ul>
            </div>
            <div>
                <p class="font-display font-extrabold text-sm mb-4">Legal</p>
                <ul class="space-y-3 text-sm font-bold text-ink-muted">
                    <li><a href="#" class="hover:text-primary focus-ring">Kebijakan Privasi</a></li>
                    <li><a href="#" class="hover:text-primary focus-ring">Syarat & Ketentuan</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t-2 border-border">
            <p class="max-w-6xl mx-auto px-5 py-6 text-xs font-bold text-ink-muted">© {{ date('Y') }} {{ $brand }}. Seluruh hak cipta dilindungi.</p>
        </div>
    </footer>

    <script>
        // Mobile menu
        const menuBtn = document.getElementById('menuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        menuBtn?.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));

        // Scroll reveal (stagger dari transition-delay inline sudah ada di elemen grid)
        const revealEls = document.querySelectorAll('.reveal');
        const stampEl = document.querySelector('.stamp');
        const fillEls = document.querySelectorAll('.fill-anim');
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        revealEls.forEach(el => io.observe(el));
        if (stampEl) io.observe(stampEl);
        fillEls.forEach(el => io.observe(el));

        // Hero card tilt mengikuti posisi mouse (nonaktif kalau prefers-reduced-motion)
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const tiltWrap = document.getElementById('heroTiltWrap');
        const heroCard = document.getElementById('heroCard');
        if (tiltWrap && heroCard && !reduceMotion) {
            tiltWrap.addEventListener('mousemove', (e) => {
                const rect = tiltWrap.getBoundingClientRect();
                const px = (e.clientX - rect.left) / rect.width - 0.5;
                const py = (e.clientY - rect.top) / rect.height - 0.5;
                heroCard.style.transform = `perspective(800px) rotateY(${px * 6}deg) rotateX(${py * -6}deg) translateZ(0)`;
            });
            tiltWrap.addEventListener('mouseleave', () => {
                heroCard.style.transform = 'perspective(800px) rotateY(0deg) rotateX(0deg)';
            });
        }
    </script>
</body>
</html>