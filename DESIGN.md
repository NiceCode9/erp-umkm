# DESIGN.md - Panduan UI/UX

Dokumen ini memastikan AI coding agent menghasilkan tampilan yang konsisten di seluruh halaman, alih-alih desain yang berbeda-beda tiap kali generate view baru. Berlaku untuk **seluruh area** (Superadmin, Owner, Kasir) — satu design system yang sama, karena seluruhnya memakai Blade + Tailwind CSS tanpa PWA.

## 1. Prinsip Desain

- **Kasir mengutamakan kecepatan** — layar transaksi harus minim klik, elemen besar dan mudah disentuh (kasir sering pakai tablet/layar sentuh di lapangan).
- **Owner mengutamakan kejelasan data** — dashboard dan laporan harus mudah dipindai (scannable), gunakan card, tabel, dan grafik ringkas.
- **Konsisten lebih penting daripada kreatif** — satu pola komponen dipakai berulang di semua modul, jangan variasikan gaya tombol/form antar halaman.
- **Mobile-first untuk area Kasir**, karena kemungkinan besar diakses dari tablet/HP di kasir fisik. Area Owner boleh desktop-first tapi tetap responsif.

## 2. Design Tokens

### Warna

Menggunakan CSS custom properties (`:root`), terinspirasi palet Duolingo — ceria, kontras jelas antar status, cocok untuk penggunaan harian di kasir.

```css
:root {
  --radius: 0.625rem;
  --background: #ffffff;
  --foreground: #3C3C3C;
  --card: #F7F7F7;
  --card-foreground: #3C3C3C;
  --popover: #F7F7F7;
  --popover-foreground: #3C3C3C;
  --primary: #58CC02;
  --primary-foreground: #ffffff;
  --secondary: #1CB0F6;
  --secondary-foreground: #3C3C3C;
  --muted: #EBEBEB;
  --muted-foreground: #777777;
  --accent: #58CC02;
  --accent-foreground: #ffffff;
  --warning: #FF9600;
  --warning-foreground: #3C3C3C;
  --destructive: #e01e2c;
  --destructive-foreground: #ffffff;
  --border: #E5E5E5;
  --input: #E5E5E5;
  --ring: #58CC02;
}
```

| Token | Penggunaan di Aplikasi |
|---|---|
| `--primary` (`#58CC02`) | Tombol utama, navbar aktif, tombol "Bayar"/"Simpan", ring fokus input |
| `--secondary` (`#1CB0F6`) | Aksi sekunder, link, elemen informasi (mis. badge "pending") |
| `--warning` (`#FF9600`) | Stok menipis, jatuh tempo mendekat — *ditambahkan karena tidak ada di palet asli, menyesuaikan gaya Duolingo* |
| `--destructive` (`#e01e2c`) | Stok habis, jatuh tempo lewat, hapus/batal, aksi berbahaya |
| `--muted` / `--muted-foreground` | Placeholder, teks sekunder, elemen nonaktif |
| `--card` | Background card dashboard, table wrapper |
| `--border` / `--input` | Garis pembatas, border input form |
| `--radius` (`0.625rem`) | Radius standar untuk card, button, input — jaga konsisten, jangan campur radius berbeda antar komponen |

**Integrasi dengan Tailwind CSS:** daftarkan seluruh token di atas ke `tailwind.config.js` melalui `theme.extend.colors` (mis. `primary: 'var(--primary)'`, `destructive: 'var(--destructive)'`, dst.) sehingga bisa dipakai langsung sebagai utility class seperti `bg-primary`, `text-destructive`, `border-border`, `rounded-[var(--radius)]`. CSS variables didefinisikan sekali di `resources/css/app.css` pada `:root`. Dengan pendekatan ini, mengganti warna brand di kemudian hari cukup mengubah nilai variable, tanpa menyentuh markup Blade. **Token ini berlaku untuk seluruh area termasuk Superadmin** — tidak ada lagi skema warna terpisah untuk Superadmin.

### Tipografi
- Font: system font stack (`font-sans` default Tailwind, atau daftarkan eksplisit di `tailwind.config.js` bila ingin pin ke `-apple-system, Segoe UI, Roboto, ...`) — tidak perlu font custom di awal, mengurangi beban loading halaman.
- Ukuran heading pakai skala default Tailwind (`text-xl`, `text-2xl`, `text-3xl`, dst.) secara konsisten per level heading — tetapkan mapping sekali (mis. `h1` = `text-3xl font-bold`, `h2` = `text-2xl font-semibold`) dan jangan variasikan ukuran ad-hoc per halaman.
- Warna teks utama pakai `text-foreground` (`#3C3C3C`), bukan hitam pekat (`#000000`) — konsisten dengan nuansa Duolingo yang lebih lembut.

### Spacing & Layout
- Gunakan skala spacing default Tailwind (`p-*`, `m-*`, `gap-*`, `space-y-*`) — hindari nilai arbitrary (`p-[13px]`) kecuali benar-benar perlu, agar spacing tetap konsisten antar halaman.
- Container utama: `max-w-screen-xl mx-auto px-4` untuk halaman data-heavy (tabel, dashboard), `max-w-2xl mx-auto` untuk form/detail yang lebih sempit.

## 3. Komponen Standar (Area Owner & Kasir)

> Tailwind tidak punya kelas komponen siap pakai seperti `btn btn-primary` milik Bootstrap — untuk menghindari AI agent menulis kombinasi utility class yang berbeda-beda tiap halaman, buat **Blade component** sekali di awal (`resources/views/components/`) untuk tiap elemen berikut, lalu pakai berulang via `<x-button>`, `<x-badge>`, dst. Ini pengganti langsung konsep "kelas Bootstrap" di dunia Tailwind.

### Tombol — `<x-button>`
- Primary action (Simpan, Bayar, Konfirmasi): `bg-primary text-primary-foreground hover:bg-primary/90 rounded-[var(--radius)] px-4 py-2 font-semibold`.
- Secondary/batal: `border border-border text-foreground hover:bg-muted rounded-[var(--radius)] px-4 py-2`.
- Aksi berbahaya (hapus, nonaktifkan): `bg-destructive text-destructive-foreground hover:bg-destructive/90`, WAJIB disertai modal konfirmasi sebelum eksekusi.
- Ukuran tombol di layar Kasir minimal `px-6 py-3 text-lg` untuk kemudahan sentuh.

### Tabel Data
- Gunakan `yajra/laravel-datatables` untuk semua tabel dengan data > 20 baris (pembelian, penjualan, stok, dll) — search, sort, pagination otomatis konsisten. Styling tabel (header, border, hover row) dibungkus sekali sebagai Blade component `<x-data-table>` memakai token `border-border`, `bg-card` untuk header.
- Kolom aksi (edit/hapus/detail) selalu di paling kanan, gunakan ikon + tooltip, bukan teks panjang.

### Form
- Label di atas input (bukan inline/floating label) untuk konsistensi dan aksesibilitas.
- Input standar: `border border-input rounded-[var(--radius)] px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent` — bungkus sebagai `<x-input>`.
- Validasi error ditampilkan sebagai teks kecil `text-destructive text-sm mt-1` di bawah masing-masing field, bukan alert tunggal di atas form.
- Field yang butuh angka besar (harga, stok) gunakan input dengan pemisah ribuan otomatis (mis. via JS formatting), tapi kirim raw number ke backend.

### Card & Dashboard Widget — `<x-card>`
- Style dasar: `bg-card text-card-foreground rounded-[var(--radius)] border border-border p-4 shadow-sm`.
- Ringkasan angka (total penjualan, stok kritis, dll) ditampilkan sebagai card dengan ikon + angka besar + label kecil di bawahnya.
- Warna aksen card mengikuti token status (mis. card stok kritis pakai border/ikon `--warning`, card omzet pakai `--primary`).

### Status Badge — `<x-badge>`
- Style dasar: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium`.
- Mapping status: `paid`/`lunas` → `bg-primary/10 text-primary`, `partial`/`sebagian` → `bg-warning/10 text-warning`, `unpaid`/`belum bayar` → `bg-destructive/10 text-destructive`, `pending` → `bg-muted text-muted-foreground`.

### Modal Konfirmasi
- Semua aksi destruktif (hapus, nonaktifkan, batalkan transaksi) WAJIB melalui modal konfirmasi — tidak boleh langsung eksekusi dari klik tombol/link.
- Bisa dibangun dengan vanilla JS (toggle class/`hidden`) sesuai stack yang sudah ditentukan. Jika ingin interaktivitas lebih ringkas, `alpine.js` (ringan, ~15kb) adalah opsi tambahan yang umum dipadukan dengan Tailwind — tapi ini opsional, bukan wajib, dan perlu ditambahkan ke daftar library di `AGENTS.md` bila jadi dipakai.

### Empty State
- Setiap tabel/list yang datanya kosong menampilkan ilustrasi/teks sederhana ("Belum ada data transaksi") + CTA jika relevan (mis. "Tambah Transaksi Baru"), bukan tabel kosong tanpa keterangan.

### Loading State
- Aksi yang memanggil server (submit form, load data Ajax/Datatable) menampilkan spinner/disable tombol sementara — mencegah double submit (penting untuk transaksi kasir & pembelian).

### Form Repeater — `<x-form-repeater>`
- Dipakai untuk semua form yang butuh input banyak baris sekaligus dalam satu submit: form pembelian (banyak bahan baku), form resep produk (banyak baris bahan baku + qty), dan form serupa di masa depan (mis. form retur multi-item).
- Pola: tombol "+ Tambah Baris" menambah baris input baru secara dinamis (client-side, pakai vanilla JS/alpine.js — lihat catatan di Modal Konfirmasi), tombol "×" di tiap baris untuk hapus baris tersebut (minimal sisakan 1 baris).
- Field di tiap baris tetap pakai `<x-input>` / dropdown standar, dibungkus dalam array name (`items[0][raw_material_id]`, `items[0][qty]`, dst.) supaya diterima sebagai array di backend saat submit.
- Validasi tetap per baris (`invalid-feedback` di bawah field yang error), bukan satu alert umum untuk semua baris.
- SEMUA baris tersimpan dalam **satu submit/transaction** — bukan simpan baris demi baris.

## 4. Halaman Kasir (Prioritas UX)

- Layar transaksi kasir adalah halaman paling sering dipakai — desain harus:
  - Daftar produk dalam grid/card besar dengan gambar, mudah dicari (search bar + kategori).
  - Keranjang transaksi selalu terlihat (sidebar atau panel tetap), update real-time saat produk ditambahkan.
  - Tombol "Bayar"/"Selesaikan Transaksi" besar, warna primary, posisi tetap terlihat tanpa perlu scroll.
  - Input diskon & indikator tax terlihat jelas sebelum konfirmasi pembayaran.
- Minimalkan jumlah halaman/klik untuk menyelesaikan satu transaksi (idealnya 1 layar, bukan multi-step wizard).

## 5. Navigasi

- Sidebar navigasi untuk Owner: dikelompokkan per domain (Dashboard, Bahan Baku, Produksi, Pembelian, Penjualan, Pengiriman, Distribusi Cabang, **Riwayat Stok** (ledger umum lintas item, lihat `PRD.md` bagian 6.3), Keuangan, Pengaturan).
- Navigasi Kasir: dibuat sangat ringkas — hanya Kasir (transaksi), Riwayat Penjualan, Profil. Jangan tampilkan menu yang tidak relevan dengan role Kasir (lihat `PERMISSIONS.md`).
- **Menu dinamis:** karena sistem permission bisa berubah, sidebar sebaiknya di-generate dari permission yang dimiliki user (bukan hardcode per role di Blade), agar konsisten dengan `PERMISSIONS.md`.

## 6. Halaman Autentikasi (Laravel Breeze)

- Halaman login/register bawaan Laravel Breeze (stack Blade) WAJIB disesuaikan ke token warna & komponen di dokumen ini (`--primary`, `<x-button>`, `<x-input>`) — jangan biarkan tampil dengan styling default Breeze yang belum disentuh.
- Karena aplikasi tidak menggunakan PWA, tidak perlu elemen ikon/manifest/splash screen — cukup halaman login yang bersih dan konsisten dengan design token, dengan logo/nama aplikasi di bagian atas form.

## 7. Area Superadmin

- Superadmin memakai **design system yang sama persis** dengan area Owner/Kasir (Blade component, token warna, tipografi) — bukan lagi tema terpisah seperti AdminLTE.
- Perbedaan cukup di navigasi (sidebar khusus Superadmin: daftar tenant, aktivasi/nonaktivasi) dan konten halaman, bukan di sistem visual.
- Tetap terapkan pola modal konfirmasi untuk aksi aktif/nonaktifkan business (konsisten dengan prinsip di bagian 3).
- Karena area ini murni operasional internal, prioritaskan kejelasan tabel/data di atas dekorasi visual — tapi tetap pakai komponen yang sama (`<x-card>`, `<x-badge>`, `<x-data-table>`) demi konsistensi kode.

## 8. Aksesibilitas Dasar

- Kontras warna teks-background minimal memenuhi standar WCAG AA (terutama untuk badge status di atas warna solid).
- Semua ikon aksi (edit/hapus/dsb) disertai `aria-label` atau tooltip teks, tidak hanya ikon tanpa keterangan.
- Ukuran target sentuh minimal 44x44px untuk elemen interaktif di area Kasir.

## 9. Format Angka & Mata Uang (Locale Indonesia)

Berlaku untuk SELURUH tampilan angka di aplikasi — konsisten di semua halaman, tidak boleh berbeda-beda cara format antar modul.

- **Format Indonesia**: titik (`.`) sebagai pemisah ribuan, koma (`,`) sebagai pemisah desimal. Contoh: `Rp 12.000`, qty `12,5`.
- **Mata uang (Rupiah)**: TIDAK menampilkan desimal sama sekali (Rupiah tidak punya sen dalam praktik sehari-hari) — selalu bulat, mis. `Rp 12.000`, bukan `Rp 12.000,00`.
- **Kuantitas/angka non-mata uang** (stok, qty resep, dll.): tampilkan **tanpa desimal kalau nilainya bulat** (`12`, bukan `12,00`), dan **tampilkan desimal HANYA kalau nilainya memang pecahan** (`12,5`, bukan dipaksa jadi `12,50` kecuali presisi dua desimal memang relevan, mis. harga per satuan kecil). Jangan tampilkan trailing zero yang tidak perlu (`12.00` → `12`, `12.50` → `12,5`).
- **Implementasi**: buat SATU helper terpusat (mis. `format_number($value)` dan `format_currency($value)` di `app/Helpers/` atau sebagai Blade directive `@rupiah($value)` / `@qty($value)`) — dipakai di SEMUA tempat yang menampilkan angka. Jangan format angka manual berulang-ulang di tiap Blade view.
- **Input form**: field angka (harga, qty) tetap terima input dengan titik/koma sesuai konvensi Indonesia di sisi tampilan, tapi VALUE yang dikirim ke backend & disimpan di database tetap format numerik standar (`.` sebagai desimal, tanpa pemisah ribuan) — konversi format hanya terjadi di layer presentasi (JS formatting saat mengetik, lalu di-parse ulang ke raw number sebelum submit), BUKAN di level penyimpanan data.
- **Library JS/chart** (Chart.js, DataTables, dll.) tetap bekerja dengan angka mentah secara internal — hanya label/tooltip yang ditampilkan ke user yang perlu diformat ulang ke locale Indonesia.

## 10. Dokumen Terkait

- `AGENTS.md` — aturan wajib Blade component & konfigurasi build Vite untuk seluruh area.
- `PERMISSIONS.md` — dasar untuk menu dinamis berbasis permission.
- `ARCHITECTURE.md` — konteks teknis layout & responsivitas (tanpa PWA).
