# DESIGN.md - Panduan UI/UX

Dokumen ini memastikan AI coding agent menghasilkan tampilan yang konsisten di seluruh halaman, alih-alih desain yang berbeda-beda tiap kali generate view baru. Berlaku untuk area `/app/*` (Tailwind CSS, PWA) — area `/superadmin/*` mengikuti konvensi bawaan AdminLTE 4 apa adanya (lihat bagian 7).

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

**Integrasi dengan Tailwind CSS:** daftarkan seluruh token di atas ke `tailwind.config.js` melalui `theme.extend.colors` (mis. `primary: 'var(--primary)'`, `destructive: 'var(--destructive)'`, dst.) sehingga bisa dipakai langsung sebagai utility class seperti `bg-primary`, `text-destructive`, `border-border`, `rounded-[var(--radius)]`. CSS variables didefinisikan sekali di `resources/css/app.css` pada `:root`. Dengan pendekatan ini, mengganti warna brand di kemudian hari cukup mengubah nilai variable, tanpa menyentuh markup Blade. Area `/superadmin/*` (AdminLTE 4) **tidak** memakai token ini — tetap pakai skema warna bawaan AdminLTE (lihat bagian 7).

### Tipografi
- Font: system font stack (`font-sans` default Tailwind, atau daftarkan eksplisit di `tailwind.config.js` bila ingin pin ke `-apple-system, Segoe UI, Roboto, ...`) — tidak perlu font custom di awal, mengurangi beban loading PWA.
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

## 4. Halaman Kasir (Prioritas UX)

- Layar transaksi kasir adalah halaman paling sering dipakai — desain harus:
  - Daftar produk dalam grid/card besar dengan gambar, mudah dicari (search bar + kategori).
  - Keranjang transaksi selalu terlihat (sidebar atau panel tetap), update real-time saat produk ditambahkan.
  - Tombol "Bayar"/"Selesaikan Transaksi" besar, warna primary, posisi tetap terlihat tanpa perlu scroll.
  - Input diskon & indikator tax terlihat jelas sebelum konfirmasi pembayaran.
- Minimalkan jumlah halaman/klik untuk menyelesaikan satu transaksi (idealnya 1 layar, bukan multi-step wizard).

## 5. Navigasi

- Sidebar navigasi untuk Owner: dikelompokkan per domain (Dashboard, Bahan Baku, Produksi, Pembelian, Penjualan, Pengiriman, Distribusi Cabang, Keuangan, Pengaturan).
- Navigasi Kasir: dibuat sangat ringkas — hanya Kasir (transaksi), Riwayat Penjualan, Profil. Jangan tampilkan menu yang tidak relevan dengan role Kasir (lihat `PERMISSIONS.md`).
- **Menu dinamis:** karena sistem permission bisa berubah, sidebar sebaiknya di-generate dari permission yang dimiliki user (bukan hardcode per role di Blade), agar konsisten dengan `PERMISSIONS.md`.

## 6. PWA - Elemen Visual Tambahan

- Ikon aplikasi (berbagai ukuran sesuai `manifest.json`) dan splash screen sederhana dengan warna `--primary` (`#58CC02`) sebagai background.
- Tombol/banner "Install Aplikasi" muncul non-intrusive (mis. di dashboard, bisa ditutup), memicu `beforeinstallprompt`.
- Indikator status koneksi (mis. banner kecil) saat aplikasi mendeteksi offline — mengingat aplikasi bersifat online-only, ini penting agar user tahu kenapa transaksi tidak bisa diproses.

## 7. Area Superadmin (AdminLTE 4)

- Ikuti struktur dan komponen bawaan AdminLTE 4 tanpa kustomisasi visual berlebihan — area ini murni operasional internal, prioritas fungsi bukan estetika.
- Tetap terapkan pola modal konfirmasi untuk aksi aktif/nonaktifkan business (konsisten dengan prinsip di bagian 3).
- Jangan bagikan file CSS/JS antara area ini dengan area `/app/*` (lihat aturan pemisahan di `AGENTS.md` bagian 4).

## 8. Aksesibilitas Dasar

- Kontras warna teks-background minimal memenuhi standar WCAG AA (terutama untuk badge status di atas warna solid).
- Semua ikon aksi (edit/hapus/dsb) disertai `aria-label` atau tooltip teks, tidak hanya ikon tanpa keterangan.
- Ukuran target sentuh minimal 44x44px untuk elemen interaktif di area Kasir.

## 9. Dokumen Terkait

- `AGENTS.md` — aturan pemisahan area & larangan mencampur asset AdminLTE/Tailwind, serta konfigurasi build Vite.
- `PERMISSIONS.md` — dasar untuk menu dinamis berbasis permission.
- `ARCHITECTURE.md` — konteks teknis layout & PWA.
