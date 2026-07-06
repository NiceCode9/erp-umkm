# ROADMAP.md - Fase Pengembangan

Urutan ini dirancang agar setiap fase menghasilkan sistem yang bisa langsung dites/dipakai (bukan sekadar potongan kode terisolasi), dan fase berikutnya selalu dibangun di atas fondasi yang sudah stabil.

## Fase 0 - Fondasi Project

- Setup project Laravel 13, konfigurasi database, environment.
- Install & konfigurasi seluruh library wajib (`spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-settings`, `spatie/laravel-medialibrary`, `spatie/laravel-backup`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `yajra/laravel-datatables`, `milon/barcode`, `intervention/image`).
- Migration dasar: `businesses`, `branches`, `users` + integrasi role Superadmin/Owner/Kasir.
- Implementasi Global Scope multi-tenant (`BelongsToBusiness`) dan middleware `EnsureBusinessIsActive` — ini fondasi keamanan, harus selesai & teruji sebelum lanjut ke modul lain.
- Setup dua layout terpisah: AdminLTE 4 (`/superadmin`) dan Tailwind CSS (`/app`), termasuk `manifest.json` + service worker dasar untuk PWA.
- Autentikasi & redirect berbasis role setelah login.

## Fase 1 - Superadmin & Manajemen Tenant

- CRUD business (tenant) oleh Superadmin.
- Aktifkan/nonaktifkan business + efeknya ke akses user terkait.
- Dashboard Superadmin sederhana (jumlah tenant, status aktif/nonaktif).
- Activity log untuk aksi aktivasi/nonaktivasi.

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

- Kelola resep produk (`product_recipes`).
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
- Uji PWA (install prompt, app-shell caching, fallback offline sederhana).
- Uji peran & permission menyeluruh sesuai `PERMISSIONS.md`.

## Fase Lanjutan (Backlog, Di Luar Cakupan Versi Awal)

- Sistem subscription/billing untuk tenant.
- Mode offline / sinkronisasi data pada PWA.
- Superadmin impersonate akun tenant.
- API publik untuk integrasi pihak ketiga.
- Approval workflow untuk pembelian besar.
- Versioning resep produksi & persentase susut otomatis.
- Tabel konversi satuan dinamis (`unit_conversions`) jika kombinasi satuan makin kompleks.

## Catatan Penggunaan Roadmap Ini dalam Vibecoding

- Kerjakan satu fase sampai benar-benar stabil (migration, model, controller, view, minimal manual testing) sebelum meminta AI agent lanjut ke fase berikutnya — menghindari akumulasi bug lintas modul yang sulit ditelusuri.
- Setiap fase yang menyentuh stok/keuangan WAJIB dicek ulang terhadap `BUSINESS-RULES.md` agar kalkulasi konsisten.
- Update `AGENTS.md` bagian Log Perubahan setiap kali ada keputusan baru yang muncul selama pengerjaan suatu fase.
