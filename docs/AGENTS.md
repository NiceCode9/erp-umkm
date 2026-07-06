# AGENTS.md - Instruksi Kerja untuk AI Coding Agent

Dokumen ini adalah panduan wajib bagi AI coding agent (opencode) saat mengerjakan project ini. Baca bersama `PRD.md`, `DATABASE.md`, `PERMISSIONS.md`, dan `BUSINESS-RULES.md` sebelum menulis kode.

## 1. Tech Stack Wajib

- Laravel 13, PHP terbaru yang kompatibel.
- MySQL, single database, multi-tenant (lihat aturan scoping di bawah — ini WAJIB, bukan opsional).
- Tailwind CSS untuk area Owner & Kasir (PWA).
- AdminLTE 4 untuk area Superadmin (non-PWA).
- Library wajib: `spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-settings`, `spatie/laravel-medialibrary`, `spatie/laravel-backup`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `yajra/laravel-datatables`, `milon/barcode`, `intervention/image`.
- Jangan menambahkan library baru di luar daftar ini tanpa alasan kuat dan mencatatnya kembali di dokumen ini.

## 2. ATURAN KERAS: Isolasi Data Multi-Tenant

Ini adalah aturan **paling kritis** di seluruh project. Kebocoran data antar tenant (business) adalah bug fatal.

- Setiap tabel transaksional (produk, bahan baku, stok, pembelian, penjualan, produksi, keuangan, pengiriman, utang-piutang) WAJIB memiliki kolom `business_id`.
- Setiap Model yang memiliki `business_id` WAJIB menggunakan **Global Scope** otomatis yang memfilter query berdasarkan `business_id` user yang sedang login.
- **DILARANG** menulis query manual (`DB::table(...)`, `Model::all()`, dsb.) yang mem-bypass global scope tanpa alasan eksplisit dan approval — jika terpaksa, filter `business_id` secara manual dan beri komentar kenapa scope di-bypass.
- Area `/superadmin/*` adalah SATU-SATUNYA area yang boleh mengakses lintas tenant (semua business). Middleware area lain harus menolak akses lintas `business_id`.
- Setiap kali menambahkan tabel/model baru, selalu tanyakan: "Apakah data ini spesifik ke satu business? Jika ya, wajib ada `business_id` + global scope."

## 3. ATURAN KERAS: Status Business Nonaktif

- Middleware global di area `/app/*` (Owner & Kasir) WAJIB mengecek `business.is_active` di setiap request.
- Jika `is_active = false`, user di-redirect ke halaman informasi akun nonaktif — TIDAK BOLEH mengizinkan akses parsial ke fitur apapun.
- Middleware ini TIDAK berlaku untuk area `/superadmin/*`.

## 4. Pemisahan Area & Layout

Project ini memiliki **dua area terpisah** dengan layout berbeda — JANGAN mencampur komponen antar area:

| Area | Prefix Route | Layout | PWA? |
|---|---|---|---|
| Superadmin | `/superadmin/*` | AdminLTE 4 | Tidak |
| Owner & Kasir | `/app/*` | Tailwind CSS | Ya (manifest.json + service worker, **online-only**, tanpa offline sync) |

- File `manifest.json` dan service worker HANYA di-load di area `/app/*`.
- Komponen/asset AdminLTE (CSS/JS-nya) TIDAK boleh ikut ter-load di area `/app/*`, dan sebaliknya. Compiled CSS Tailwind (`app.css` hasil build Vite) TIDAK boleh ikut ter-load di area `/superadmin/*`, dan sebaliknya asset AdminLTE tidak boleh masuk ke bundle Vite area `/app/*`.
- Tailwind dikompilasi via Laravel Vite (`resources/css/app.css` + `tailwind.config.js`), khusus untuk area `/app/*`. AdminLTE 4 dimuat sebagai asset statis terpisah (CDN atau `public/vendor/adminlte`) untuk area `/superadmin/*`, tidak melalui pipeline Vite/Tailwind.
- **Keputusan tegas styling Tailwind:** semua elemen UI berulang (tombol, badge, card, input, dll.) WAJIB dibuat sebagai **Blade component** di `resources/views/components/` (`<x-button>`, `<x-badge>`, `<x-card>`, `<x-input>`, dst.), bukan `@apply` custom class dan bukan menulis kombinasi utility class Tailwind langsung berulang-ulang di tiap Blade view. Rincian tiap komponen ada di `DESIGN.md` bagian 3. Ini konsisten dipakai selama seluruh project — jangan campur pendekatan lain di tengah jalan.
- Service worker cukup untuk app-shell caching (asset statis, splash screen, install prompt) — JANGAN implementasi caching untuk data transaksi/API response, karena aplikasi mewajibkan koneksi online.

## 5. Konvensi Kode

- Struktur folder mengikuti konvensi default Laravel; jika perlu modul terpisah (misal per domain: `Sales`, `Purchasing`, `Production`, `Inventory`, `Finance`), gunakan struktur folder yang konsisten dan didokumentasikan ulang di sini begitu diputuskan.
- Naming tabel & kolom: `snake_case`, konsisten dengan konvensi Laravel/Eloquent standar.
- Semua migration menyertakan foreign key constraint yang jelas, termasuk `business_id` dan `cabang_id` di mana relevan.
- Gunakan Form Request untuk validasi, jangan validasi inline di controller untuk form yang kompleks.
- Gunakan Service/Action class untuk logika bisnis kompleks (misalnya kalkulasi BOM produksi, FEFO, kalkulasi diskon+tax) — jangan taruh logika ini langsung di controller.
- Setiap perubahan stok (bahan baku maupun produk jadi) WAJIB melalui satu service terpusat (misal `StockService`) agar histori pergerakan stok konsisten dan bisa diaudit — dilarang mengubah kolom stok langsung dari controller manapun.

## 6. Snapshot Data Transaksi

- Nilai diskon, tax, dan harga pada transaksi penjualan WAJIB disimpan sebagai **snapshot** di tabel transaksi (bukan hanya referensi ke tabel setting/produk), agar riwayat transaksi lama tidak berubah jika setting/harga diubah di kemudian hari.

## 7. Activity Log

Seluruh aksi berikut WAJIB tercatat via `spatie/laravel-activitylog`:
- Aktivasi/nonaktivasi business oleh Superadmin.
- Transaksi pembelian, penjualan, produksi.
- Perubahan stok manual (stok opname).
- Perubahan setting tax/diskon per cabang.

## 8. Hal yang TIDAK Boleh Dilakukan AI Agent

- Jangan membuat fitur subscription/billing — eksplisit di luar cakupan versi ini (lihat `PRD.md` bagian 8).
- Jangan mengimplementasikan mode offline/IndexedDB/background sync — PWA bersifat online-only.
- Jangan membuat query yang mengabaikan `business_id` scoping.
- Jangan menambah role baru di luar Superadmin/Owner/Kasir tanpa konfirmasi eksplisit.
- Jangan mengubah struktur database tanpa memperbarui `DATABASE.md` di saat yang sama.

## 9. Dokumen Referensi

Selalu rujuk balik ke:
- `PRD.md` untuk definisi fitur & scope.
- `DATABASE.md` untuk struktur tabel & relasi.
- `PERMISSIONS.md` untuk detail hak akses per role.
- `BUSINESS-RULES.md` untuk logika kalkulasi (BOM, FEFO, tax, diskon, utang-piutang).
- `ROADMAP.md` untuk prioritas pengerjaan per fase.

## 10. Log Perubahan Dokumen Ini

Perbarui bagian ini setiap kali ada keputusan arsitektur baru yang disepakati selama sesi vibecoding.

| Tanggal | Perubahan |
|---|---|
| - | Versi awal dibuat berdasarkan hasil diskusi PRD |
