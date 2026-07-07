# AGENTS.md - Instruksi Kerja untuk AI Coding Agent

Dokumen ini adalah panduan wajib bagi AI coding agent (opencode) saat mengerjakan project ini. Baca bersama `PRD.md`, `DATABASE.md`, `PERMISSIONS.md`, dan `BUSINESS-RULES.md` sebelum menulis kode.

## 1. Tech Stack Wajib

- Laravel 13, PHP terbaru yang kompatibel.
- MySQL, single database, multi-tenant (lihat aturan scoping di bawah — ini WAJIB, bukan opsional).
- Blade + Tailwind CSS untuk SELURUH area (Superadmin, Owner, Kasir) — satu stack visual, responsif, tanpa PWA.
- Laravel Breeze (stack Blade) untuk autentikasi — sudah default menyertakan Tailwind CSS, cocok dengan stack di atas.
- Library wajib: `spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-settings`, `spatie/laravel-medialibrary`, `spatie/laravel-backup`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `yajra/laravel-datatables`, `milon/barcode`, `intervention/image`.
- Jangan menambahkan library baru di luar daftar ini tanpa alasan kuat dan mencatatnya kembali di dokumen ini.

## 2. ATURAN KERAS: Isolasi Data Multi-Tenant

Ini adalah aturan **paling kritis** di seluruh project. Kebocoran data antar tenant (business) adalah bug fatal.

- Setiap tabel transaksional (produk, bahan baku, stok, pembelian, penjualan, produksi, keuangan, pengiriman, utang-piutang) WAJIB memiliki kolom `business_id`.
- Setiap Model yang memiliki `business_id` WAJIB menggunakan **Global Scope** otomatis yang memfilter query berdasarkan `business_id` user yang sedang login.
- **DILARANG** menulis query manual (`DB::table(...)`, `Model::all()`, dsb.) yang mem-bypass global scope tanpa alasan eksplisit dan approval — jika terpaksa, filter `business_id` secara manual dan beri komentar kenapa scope di-bypass.
- Area `/superadmin/*` adalah SATU-SATUNYA area yang boleh mengakses lintas tenant (semua business). Middleware area lain harus menolak akses lintas `business_id`.
- Setiap kali menambahkan tabel/model baru, selalu tanyakan: "Apakah data ini spesifik ke satu business? Jika ya, wajib ada `business_id` + global scope."

### 2.1 PENGECUALIAN KRITIS: Model `User` TIDAK BOLEH Pakai Mekanisme Scope yang Sama

Model `User` memang punya kolom `business_id`, TAPI **tidak boleh** menerapkan Global Scope generik yang bergantung pada `auth()->user()->business_id` seperti model lain. Alasan: `User` adalah model yang dipakai Laravel untuk resolve "siapa yang sedang login" (`Auth::user()`). Jika scope-nya sendiri bergantung pada `auth()->user()`, terjadi **infinite recursion**: resolve user → query users → kena scope → scope butuh `auth()->user()` → resolve user lagi → ulangi terus → PHP crash (stack overflow) **tanpa tercatat di log Laravel sama sekali** (karena crash terjadi sebelum exception handler sempat jalan).

Gejala bug ini sangat khas: aplikasi normal di halaman guest (welcome, login), tapi 500 di SEMUA halaman setelah login berhasil, dan `laravel.log` kosong. Jika menemukan pola ini, curigai dulu recursive scope di model `User`.

**Solusi yang benar:**
- JANGAN terapkan trait `BelongsToBusiness` generik ke model `User`.
- Untuk kebutuhan filter user per business (mis. Owner melihat daftar Kasir miliknya), lakukan **filter eksplisit di controller/query** (`User::where('business_id', $businessId)->where('branch_id', ...)`),  BUKAN via Global Scope otomatis.
- Isolasi tenant untuk siapa-yang-boleh-login-akses-apa tetap dijamin oleh middleware (`EnsureBusinessIsActive`, cek role Spatie), bukan oleh Global Scope pada model User itu sendiri.

## 3. ATURAN KERAS: Status Business Nonaktif

- Middleware global di area `/app/*` (Owner & Kasir) WAJIB mengecek `business.is_active` di setiap request.
- Jika `is_active = false`, user di-redirect ke halaman informasi akun nonaktif — TIDAK BOLEH mengizinkan akses parsial ke fitur apapun.
- Middleware ini TIDAK berlaku untuk area `/superadmin/*`.

## 3.1 ATURAN KERAS: Autentikasi & Onboarding

- **Register publik DIHAPUS/DINONAKTIFKAN.** Tidak ada pendaftaran self-service untuk tenant/business baru. Hapus route `/register`, `RegisteredUserController` (atau nonaktifkan total), dan link "Register" di halaman login bawaan Breeze.
- Satu-satunya cara akun baru dibuat:
  - **Superadmin** membuat business baru sekaligus akun Owner awal (email & password) dalam satu form.
  - **Owner** membuat akun Kasir (sesuai `PERMISSIONS.md`).
- **Satu controller autentikasi, bukan dua.** Gunakan `AuthenticatedSessionController` bawaan Breeze sebagai satu-satunya controller login — override method `store()` untuk menambahkan redirect berbasis role setelah login berhasil (Superadmin → `/superadmin/dashboard`, Owner/Kasir → `/app/dashboard`). **Hapus** controller custom terpisah (mis. `LoginController`) yang duplikat — jangan ada dua controller yang menangani flow login yang sama.
- Pengecekan `business.is_active` (lihat bagian 3) tetap dilakukan di **middleware**, BUKAN di controller login — controller login hanya menangani autentikasi + redirect by role.
- **Hapus route/view dashboard default Breeze** (`/dashboard` mengarah ke `resources/views/dashboard.blade.php` bawaan Breeze) — route ini tidak relevan karena redirect final selalu ke `/superadmin/dashboard` atau `/app/dashboard`.

## 4. Pemisahan Area & Komponen UI

Project ini memiliki **dua area route terpisah** berdasarkan role (untuk kejelasan middleware & organisasi kode), namun keduanya memakai **satu stack visual yang sama**: Blade + Tailwind CSS. Tidak ada perbedaan framework UI antar area seperti sebelumnya (AdminLTE vs Tailwind) — ini sudah tidak berlaku lagi.

| Area | Prefix Route | Layout |
|---|---|---|
| Superadmin | `/superadmin/*` | Blade + Tailwind CSS |
| Owner & Kasir | `/app/*` | Blade + Tailwind CSS |

- Tailwind dikompilasi via Laravel Vite (`resources/css/app.css` + `tailwind.config.js`), berlaku untuk SELURUH area — satu pipeline build, satu `tailwind.config.js`, `content` di-scope ke seluruh `resources/views/**` (superadmin & app sekaligus).
- **Font: pakai system font stack** (`font-sans` default Tailwind) sesuai `DESIGN.md` bagian 2 — **hapus** konfigurasi font `Figtree` bawaan Breeze dari `tailwind.config.js`, jangan pertahankan default Breeze di sini.
- **Design token WAJIB didaftarkan** di `resources/css/app.css` (`:root` dengan CSS variables sesuai `DESIGN.md` bagian 2) dan di `tailwind.config.js` (`theme.extend.colors` memetakan ke variables tersebut) — jangan biarkan `app.css`/`tailwind.config.js` kosong dari token ini, karena seluruh komponen di `DESIGN.md` bagian 3 bergantung pada token ini tersedia.
- **Tidak ada PWA.** Jangan buat `manifest.json`, service worker, atau logika install-prompt apapun — ini keputusan final, bukan opsional.
- Kedua area boleh **berbagi Blade component dasar** (`<x-button>`, `<x-badge>`, `<x-card>`, `<x-input>`, dst.) karena satu design system yang sama (lihat `DESIGN.md`). Perbedaan antar area cukup di level layout wrapper (sidebar/navigasi berbeda sesuai role) dan konten halaman, bukan di sistem komponen UI-nya.
- **Keputusan tegas styling Tailwind:** semua elemen UI berulang (tombol, badge, card, input, dll.) WAJIB dibuat sebagai **Blade component** di `resources/views/components/`, bukan `@apply` custom class dan bukan menulis kombinasi utility class Tailwind langsung berulang-ulang di tiap Blade view. Rincian tiap komponen ada di `DESIGN.md` bagian 3. Ini konsisten dipakai selama seluruh project — jangan campur pendekatan lain di tengah jalan.
- Autentikasi memakai scaffolding **Laravel Breeze (stack Blade)** — sesuaikan/perluas view bawaan Breeze agar konsisten dengan token warna & komponen di `DESIGN.md`, jangan biarkan halaman login/register memakai styling default Breeze yang tidak disesuaikan.

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
- Jangan membuat PWA (manifest.json, service worker, install prompt) — aplikasi murni web responsif biasa (lihat bagian 4).
- Jangan mengimplementasikan mode offline/IndexedDB/background sync.
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
|---|---|---|
| - | Versi awal dibuat berdasarkan hasil diskusi PRD |
| - | Perubahan besar: seluruh area (Superadmin, Owner, Kasir) memakai Blade + Tailwind CSS (AdminLTE 4 dihapus); PWA dihapus total (tidak ada manifest/service worker); autentikasi memakai Laravel Breeze stack Blade |
| - | Bug fix kritis: model User TIDAK BOLEH pakai Global Scope generik BelongsToBusiness (menyebabkan infinite recursion/crash setelah login) — lihat bagian 2.1 |
| - | Keputusan: Register publik dihapus (tenant hanya dibuat oleh Superadmin); satu controller auth (AuthenticatedSessionController diperluas, LoginController custom dihapus); font pakai system stack (Figtree dihapus) |
| - | Bug fix kritis: model User TIDAK BOLEH pakai Global Scope generik BelongsToBusiness (menyebabkan infinite recursion/crash setelah login) — lihat bagian 2.1 |
