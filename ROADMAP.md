# ROADMAP.md - Fase Pengembangan

Urutan ini dirancang agar setiap fase menghasilkan sistem yang bisa langsung dites/dipakai (bukan sekadar potongan kode terisolasi), dan fase berikutnya selalu dibangun di atas fondasi yang sudah stabil.

## Fase 0 - Fondasi Project

- Setup project Laravel 13, konfigurasi database, environment.
- Install Laravel Breeze (stack Blade) untuk autentikasi, sesuaikan view login/register ke token warna & komponen di `DESIGN.md`.
- Install & konfigurasi seluruh library wajib (`spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-settings`, `spatie/laravel-medialibrary`, `spatie/laravel-backup`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `yajra/laravel-datatables`, `milon/barcode`, `intervention/image`).
- Migration dasar: `businesses`, `branches`, `users` + integrasi role Superadmin/Owner/Kasir.
- Implementasi Global Scope multi-tenant (`BelongsToBusiness`) dan middleware `EnsureBusinessIsActive` — ini fondasi keamanan, harus selesai & teruji sebelum lanjut ke modul lain.
- Setup dua layout Blade terpisah berdasarkan role (`/superadmin`, `/app`) — keduanya memakai Blade + Tailwind CSS yang sama, tanpa PWA (tidak ada manifest.json/service worker).
- Buat Blade component dasar (`<x-button>`, `<x-badge>`, `<x-card>`, `<x-input>`) sesuai `DESIGN.md` bagian 3, dipakai bersama oleh kedua area.
- Redirect berbasis role setelah login (Superadmin → `/superadmin/dashboard`, Owner/Kasir → `/app/dashboard`).

## Fase 1 - Superadmin & Manajemen Tenant

- CRUD business (tenant) oleh Superadmin — **form Create Business menyertakan pembuatan akun Owner awal sekaligus** (nama, email, password Owner), sesuai keputusan Register tertutup di `AGENTS.md` bagian 3.1. Satu form, satu submit, langsung menghasilkan business + user Owner pertama yang terhubung ke business tersebut.
- **Kelola penuh tiap tenant dari panel Superadmin** (ditambahkan belakangan, lihat `PRD.md` bagian 5 dan `ARCHITECTURE.md` bagian 2.1): dari halaman detail business, Superadmin dapat menambah cabang, menambah Owner tambahan, dan menambah akun Kasir untuk business tersebut — pakai business context eksplisit via route nested (`/superadmin/businesses/{business}/...`), BUKAN scope otomatis dari user login.
- Aktifkan/nonaktifkan business + efeknya ke akses user terkait.
- Dashboard Superadmin sederhana (jumlah tenant, status aktif/nonaktif).
- Activity log untuk aksi aktivasi/nonaktivasi, dan untuk pembuatan business baru.

## Fase 2 - Master Data & Manajemen Cabang/User

- CRUD cabang oleh Owner.
- CRUD user Kasir oleh Owner (assign ke cabang).
- Master data: bahan baku, produk, multi-satuan (`product_units`), supplier, customer.
- Setting tax per cabang (`branch_settings`).

## Fase 3 - Pembelian & Stok Bahan Baku (FEFO)

- Transaksi pembelian bahan baku oleh Owner.
- Pembuatan batch otomatis per pembelian (`raw_material_batches`).
- Ledger stok terpusat (`stock_movements`) mulai aktif dari fase ini.
- Utang ke supplier + pencatatan cicilan pembayaran.
- Retur pembelian.
- Notifikasi stok minimum.

## Fase 4 - Produksi (BOM + FEFO)

- Kelola resep produk — mendukung banyak resep per produk (`recipes` + `recipe_items`), Owner pilih resep saat produksi.
- Production order: validasi stok cukup, pengurangan otomatis bahan baku mengikuti FEFO, penambahan stok produk jadi.
- Riwayat produksi & pencatatan konsumsi per batch (`production_consumptions`).

## Fase 5 - Penjualan (Kasir) & Shift

- Modul kasir: transaksi penjualan, pemilihan produk & satuan (eceran/borongan).
- Kalkulasi diskon (nominal/persen per transaksi) & tax (snapshot per transaksi).
- Shift kasir: buka/tutup shift, rekonsiliasi kas.
- Cetak struk & scan barcode produk.
- Riwayat penjualan (Kasir: miliknya sendiri, Owner: semua cabang).

## Fase 6 - Piutang, Retur Penjualan, & Pengiriman ke Pembeli

- Piutang dari pembeli: pencatatan transaksi tempo, cicilan pembayaran (Kasir & Owner).
- Notifikasi jatuh tempo utang-piutang (gabungan supplier & pembeli).
- Retur penjualan.
- Modul pengiriman ke pembeli (ecer & borongan).

## Fase 7 - Distribusi Stok Antar Cabang

- CRUD distribusi stok (`stock_distributions`) oleh Owner.
- Alur status dikirim → diterima, termasuk penanganan stok "dalam perjalanan".
- Penanganan FEFO saat batch bahan baku berpindah cabang.

## Fase 8 - Stok Opname & Laporan Keuangan

- Modul stok opname (bahan baku & produk jadi, per batch bila relevan).
- Laporan keuangan lengkap: penjualan per cabang & konsolidasi, utang-piutang, stok, produksi.
- Export Excel & PDF untuk seluruh laporan.
- Dashboard Owner & Kasir versi lengkap (grafik ringkasan, stok kritis, jatuh tempo).

## Fase 9 - Pengerasan (Hardening) & Kesiapan Rilis

- Review menyeluruh isolasi data multi-tenant (audit ulang semua query, pastikan tidak ada kebocoran antar business).
- Setup backup database terjadwal.
- Optimasi query & index database berdasarkan pola akses nyata.
- Uji responsivitas di berbagai breakpoint (khususnya halaman Kasir di tablet/HP).
- Uji peran & permission menyeluruh sesuai `PERMISSIONS.md`.

## Fase Lanjutan (Backlog, Di Luar Cakupan Versi Awal)

- Sistem subscription/billing untuk tenant.
- PWA (installable app) — bila di masa depan dibutuhkan kembali.
- Superadmin impersonate akun tenant.
- API publik untuk integrasi pihak ketiga.
- Approval workflow untuk pembelian besar.
- Versioning resep produksi & persentase susut otomatis.
- Tabel konversi satuan dinamis (`unit_conversions`) jika kombinasi satuan makin kompleks.

## Catatan Penggunaan Roadmap Ini dalam Vibecoding

- Kerjakan satu fase sampai benar-benar stabil (migration, model, controller, view, minimal manual testing) sebelum meminta AI agent lanjut ke fase berikutnya — menghindari akumulasi bug lintas modul yang sulit ditelusuri.
- Setiap fase yang menyentuh stok/keuangan WAJIB dicek ulang terhadap `BUSINESS-RULES.md` agar kalkulasi konsisten.
- Update `AGENTS.md` bagian Log Perubahan setiap kali ada keputusan baru yang muncul selama pengerjaan suatu fase.