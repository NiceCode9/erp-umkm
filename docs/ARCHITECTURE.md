# ARCHITECTURE.md - Arsitektur Teknis

## 1. Gambaran Umum

Aplikasi berjalan sebagai satu project Laravel 13 dengan **dua area terpisah** dan pola **multi-tenant single database**. Lihat `PRD.md` untuk konteks bisnis lengkap, `DATABASE.md` untuk skema, dan `AGENTS.md` untuk aturan keras yang wajib diikuti AI agent.

```
                    ┌─────────────────────────┐
                    │   Single MySQL Database  │
                    │  (semua tenant, shared)  │
                    └──────────┬──────────────┘
                               │
              ┌────────────────┴────────────────┐
              │                                  │
    ┌─────────▼─────────┐            ┌───────────▼───────────┐
    │   /superadmin/*    │            │        /app/*          │
    │   Layout: AdminLTE4│            │   Layout: Tailwind CSS   │
    │   Bukan PWA         │            │   PWA (online-only)    │
    │   Akses lintas tenant│           │   Scoped per business_id│
    │   Role: Superadmin  │            │   Role: Owner, Kasir    │
    └─────────────────────┘            └─────────────────────────┘
```

## 2. Strategi Multi-Tenancy

- **Pendekatan:** Shared database, shared schema, isolasi via kolom `business_id` + Global Scope (bukan database-per-tenant, bukan schema-per-tenant).
- **Alasan:** Skala UMKM (jumlah baris per tenant relatif kecil), operasional lebih sederhana, biaya hosting lebih rendah, cocok untuk fase awal produk.
- **Implementasi Global Scope:**
  - Buat trait/base model, mis. `BelongsToBusiness`, yang otomatis menambahkan `where business_id = auth()->user()->business_id` pada setiap query, kecuali untuk user dengan role Superadmin.
  - Terapkan trait ini ke semua model yang memiliki kolom `business_id` (lihat daftar lengkap tabel di `DATABASE.md`).
  - Saat membuat record baru, `business_id` diisi otomatis dari user yang login (melalui model event `creating`), tidak boleh diinput manual dari form/request.
- **Middleware tambahan:**
  - `EnsureBusinessIsActive` — cek `business.is_active`, hanya berlaku di grup route `/app/*`.
  - `EnsureBranchAccess` — untuk Kasir, pastikan resource yang diakses (mis. `sale_id`) memang milik `branch_id` miliknya.

## 3. Struktur Area & Routing

```
routes/
  web.php              -> redirect awal berdasarkan role setelah login
  superadmin.php        -> group prefix 'superadmin', middleware ['auth','role:Superadmin']
  app.php                -> group prefix 'app', middleware ['auth','role:Owner|Kasir','business.active']
```

- Autentikasi tunggal (satu tabel `users`, satu halaman login) — setelah login, redirect ditentukan oleh role:
  - Superadmin → `/superadmin/dashboard`
  - Owner/Kasir → `/app/dashboard`
- Layout Blade terpisah total: `resources/views/superadmin/layouts/app.blade.php` (AdminLTE 4) vs `resources/views/app/layouts/app.blade.php` (Tailwind CSS + PWA meta tags).
- **Build asset:** Tailwind CSS dikompilasi via Laravel Vite (`resources/css/app.css`, `tailwind.config.js` dengan `content` di-scope ke `resources/views/app/**`) — hanya untuk area `/app/*`. AdminLTE 4 dimuat sebagai asset statis terpisah (CDN atau `public/vendor/adminlte`), tidak diproses lewat Vite/Tailwind agar kedua sistem styling tidak saling bentrok class-nya.

## 4. Modularisasi Kode (Domain-Based)

Disarankan mengelompokkan logika bisnis per domain agar mudah dikelola AI agent secara bertahap, alih-alih satu folder `Http/Controllers` besar tanpa struktur:

```
app/
  Domain/
    Tenant/            (business, branch, deactivation logic)
    Inventory/          (raw materials, batches, product stock, stock_movements)
    Production/         (BOM, production orders, FEFO consumption)
    Purchasing/          (purchases, purchase payments, purchase returns, hutang supplier)
    Sales/                (sales, sale items, payments, sale returns, piutang, diskon, tax)
    Distribution/          (shipments ke pembeli, stock_distributions antar cabang)
    Reporting/              (laporan keuangan, export Excel/PDF)
  Http/
    Controllers/
      Superadmin/
      App/
        Owner/
        Kasir/
  Services/                  (StockService, ProductionService, SalesCalculationService, dll)
```

- Service class menjadi satu-satunya pintu masuk untuk operasi yang menyentuh stok atau kalkulasi finansial (lihat `AGENTS.md` bagian 5 & `BUSINESS-RULES.md`).

## 5. PWA (Online-Only)

- `manifest.json` di root public, hanya di-load pada layout area `/app/*`.
- Service worker (`sw.js`) didaftarkan hanya di layout `/app/*`, dengan scope terbatas untuk:
  - Caching asset statis (CSS, JS, ikon, font).
  - Menampilkan halaman fallback sederhana "Anda sedang offline, silakan cek koneksi internet" jika request gagal karena tidak ada jaringan.
- **Tidak ada** caching untuk response data/API, tidak ada IndexedDB, tidak ada background sync — sesuai keputusan aplikasi bersifat online-only (lihat `PRD.md` bagian 8, Out of Scope).
- Install prompt (`beforeinstallprompt`) ditampilkan di dashboard Owner/Kasir agar user bisa "Add to Home Screen".

## 6. Alur Data Antar Modul (Ringkasan)

```
Pembelian Bahan Baku ──► raw_material_batches (FEFO) ──► Produksi (BOM) ──► product_stocks
                                                                              │
                                                                              ▼
                                                          Penjualan (Kasir) ──► sale_items, stock keluar
                                                                              │
                                                                              ▼
                                                          Pengiriman ke Pembeli (ecer/borongan)

product_stocks / raw_material_batches ──► Distribusi Stok Antar Cabang ──► product_stocks / raw_material_batches (cabang lain)

Semua pergerakan stok di atas ──► dicatat terpusat di stock_movements (audit trail)
Semua transaksi finansial (purchases, sales) ──► purchase_payments / sale_payments ──► status utang-piutang
```

## 7. Keamanan & Audit

- Isolasi data multi-tenant adalah lapisan keamanan utama — lihat aturan keras di `AGENTS.md` bagian 2.
- `spatie/laravel-activitylog` mencatat aksi-aksi kritis (lihat daftar di `AGENTS.md` bagian 7).
- Backup database berkala via `spatie/laravel-backup`, terjadwal (mis. harian) menggunakan Laravel Scheduler.
- Password hashing standar Laravel (bcrypt/argon2), tidak ada penyimpanan password dalam bentuk lain.

## 8. Deployment (Gambaran Awal)

- Web server: Apache/Nginx + PHP-FPM (sesuaikan dengan hosting yang tersedia).
- Queue: disarankan menggunakan queue worker (database driver cukup untuk awal) untuk proses berat seperti export laporan besar atau notifikasi, agar tidak memblokir request utama.
- Scheduler Laravel aktif untuk: backup database, notifikasi stok minimum, notifikasi jatuh tempo utang-piutang.

## 9. Dokumen Terkait

- `DATABASE.md` — skema lengkap semua tabel yang direferensikan arsitektur ini.
- `AGENTS.md` — aturan implementasi wajib untuk AI coding agent.
- `BUSINESS-RULES.md` — detail logika yang dijalankan oleh Service class di atas.
- `ROADMAP.md` — urutan pembangunan modul-modul ini per fase.
