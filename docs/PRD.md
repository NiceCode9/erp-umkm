# PRD - ERP UMKM (Multi-Tenant SaaS)

> Catatan: Nama aplikasi masih placeholder, sesuaikan sesuai kebutuhan branding.

## 1. Latar Belakang & Tujuan

Aplikasi ini dibangun untuk membantu pelaku UMKM mengelola data penjualan, stok, produksi, keuangan, dan utang-piutang secara terpusat — terutama UMKM yang sudah memiliki atau sedang mengembangkan **banyak cabang**.

Aplikasi berjalan sebagai **platform SaaS multi-tenant**: satu instalasi aplikasi (single database) melayani banyak UMKM (tenant) sekaligus, di mana setiap UMKM memiliki data yang terisolasi satu sama lain.

## 2. Tech Stack

| Komponen | Pilihan |
|---|---|
| Framework | Laravel 13 |
| Database | MySQL (single database, shared, multi-tenant via `business_id`) |
| UI Owner & Kasir | Tailwind CSS + PWA (installable, **online-only**, tanpa offline sync) |
| UI Superadmin | AdminLTE 4 (bukan PWA) |
| Role & Permission | spatie/laravel-permission |
| Activity Log | spatie/laravel-activitylog |
| Export Excel | maatwebsite/excel |
| Export PDF | barryvdh/laravel-dompdf |
| Datatable | yajra/laravel-datatables |
| Media/Foto Produk | spatie/laravel-medialibrary |
| Setting (tax on/off, dll) | spatie/laravel-settings |
| Backup Database | spatie/laravel-backup |
| Barcode | milon/barcode |
| Image Processing | intervention/image |

## 3. Arsitektur Multi-Tenant

- Struktur data mengikuti hierarki: **Business (Tenant/UMKM) → Cabang → User (Owner/Kasir)**.
- Hampir seluruh tabel transaksional (produk, bahan baku, stok, penjualan, pembelian, keuangan) wajib memiliki kolom `business_id`.
- Isolasi data antar tenant WAJIB diterapkan melalui **Global Scope** otomatis berdasarkan `business_id` user yang login (kecuali area Superadmin, yang mengakses lintas tenant).
- Superadmin **tidak terikat** pada `business_id` tertentu.
- Terdapat dua area terpisah dalam satu project:
  - `/superadmin/*` — layout AdminLTE 4, non-PWA.
  - `/app/*` (Owner & Kasir) — layout Tailwind CSS, PWA (manifest.json + service worker untuk app-shell caching, tanpa offline transaksi).

## 4. Role & Hak Akses (Ringkasan)

| Role | Cakupan Akses |
|---|---|
| **Superadmin** | Kelola seluruh tenant (business): lihat, aktifkan/nonaktifkan. Tidak mengelola operasional harian tenant. |
| **Owner** | Mengelola seluruh cabang miliknya: bahan baku, pembelian, produksi, stok, pengiriman, penjualan (monitoring), keuangan, utang-piutang, user (kasir), setting tax & diskon per cabang. |
| **Kasir** | Akses terbatas: menu Kasir (transaksi jual), riwayat penjualan miliknya sendiri, profile pengguna. Terikat ke satu cabang tertentu. |

Detail matrix permission akan dijabarkan lengkap di `PERMISSIONS.md`.

## 5. Manajemen Tenant (Business) oleh Superadmin

- Superadmin dapat melihat daftar seluruh business (UMKM) yang terdaftar.
- Superadmin dapat **mengaktifkan/menonaktifkan** sebuah business.
- Jika business dinonaktifkan (`is_active = false`):
  - Seluruh user (Owner & Kasir) yang terikat pada business tersebut **tidak dapat mengakses aplikasi** (di-redirect ke halaman informasi akun nonaktif).
  - Middleware global mengecek status `business.is_active` di setiap request area `/app/*`.
- Tindakan aktif/nonaktifkan tercatat di activity log (siapa, kapan).
- **Di luar cakupan versi ini:** sistem subscription/billing/paket berbayar. Fitur ini murni on/off manual oleh Superadmin.

## 6. Modul Fungsional

### 6.1 Manajemen Cabang
- Owner dapat menambah, mengubah, menonaktifkan cabang.
- Setiap cabang memiliki stok bahan baku & produk sendiri (tidak tercampur antar cabang, kecuali melalui modul pengiriman).

### 6.2 Manajemen Pengguna
- Owner membuat akun Kasir dan menugaskannya ke cabang tertentu.
- Satu Kasir terikat ke satu cabang (asumsi awal; dikonfirmasi ulang saat drafting ERD bila ada kebutuhan kasir lintas-cabang).

### 6.3 Bahan Baku & Stok
- Data bahan baku per cabang: nama, satuan, stok saat ini, harga rata-rata/terakhir.
- Penerapan **FEFO** (First Expired First Out) untuk bahan baku yang memiliki tanggal kedaluwarsa — dikelola berbasis batch/lot.
- Stok opname: penyesuaian manual stok dengan pencatatan alasan (rusak, hilang, selisih hitung).
- Notifikasi stok minimum (reorder alert).

### 6.4 Pembelian Bahan Baku
- Transaksi pembelian **dilakukan oleh Owner**.
- Pembelian dapat menimbulkan **utang ke supplier** (lihat modul 6.9).
- Setiap pembelian menambah stok bahan baku sesuai batch (untuk keperluan FEFO).

### 6.5 Produksi
- Produk jadi memiliki **resep (BOM - Bill of Materials)**: daftar bahan baku beserta jumlah kebutuhan per satuan produk.
- Saat produksi dijalankan (production order), sistem otomatis mengurangi stok bahan baku berdasarkan formula:
  `qty_dikurangi = qty_per_unit_pada_resep × jumlah_diproduksi`
- Pengurangan bahan baku mengikuti urutan FEFO antar batch.
- Mendukung konversi satuan (mis. resep dalam gram, stok dalam kg).
- Opsional (fase lanjutan): persentase susut/waste produksi, versioning resep.
- Hasil produksi menambah stok produk jadi di cabang terkait.

### 6.6 Produk & Stok Produk Jadi
- Produk jadi memiliki multi-satuan (eceran/pcs, borongan/dus atau karton) dengan konversi otomatis.
- Foto produk (melalui medialibrary).

### 6.7 Pengiriman ke Pembeli
- Mendukung dua skema:
  - **Beli Ecer** — pengiriman/pengambilan satuan kecil.
  - **Borongan** — pengiriman dalam jumlah besar/grosir.
- Mencakup **barang yang sudah terjual** (pengiriman ke pembeli setelah transaksi penjualan).

### 6.7.1 Distribusi Stok Antar Cabang
- Modul terpisah dari pengiriman ke pembeli, dikelola oleh Owner.
- Memindahkan stok bahan baku dan/atau produk jadi dari satu cabang ke cabang lain (misal cabang pusat mendistribusikan ke cabang lain).
- Alur status: dikirim → diterima, dengan stok "dalam perjalanan" tidak tersedia untuk dijual/produksi sampai dikonfirmasi diterima oleh cabang tujuan.

### 6.8 Penjualan (Kasir)
- Kasir melakukan transaksi penjualan di cabangnya.
- Mendukung **diskon** (nominal atau persen) **per transaksi** (bukan per item).
- Mendukung **tax** yang dapat di-**setting on/off per cabang** oleh Owner. Jika aktif, seluruh transaksi penjualan di cabang tersebut otomatis dikenakan tax sesuai persentase yang di-setting.
- Nilai diskon & tax yang berlaku pada suatu transaksi disimpan sebagai **snapshot** pada data transaksi (bukan referensi live ke setting), agar riwayat transaksi lama tidak berubah bila setting diubah di kemudian hari.
- Riwayat penjualan dapat dilihat: Kasir hanya miliknya sendiri, Owner seluruh cabang.
- Dukungan cetak struk (thermal printer) dan barcode/QR produk untuk mempercepat transaksi.

### 6.9 Utang Piutang
- **Utang ke Supplier**: timbul dari transaksi pembelian bahan baku yang belum lunas.
- **Piutang dari Pembeli**: timbul dari transaksi penjualan yang belum lunas (kredit/tempo).
- Pencatatan pembayaran cicilan/pelunasan.
- Notifikasi jatuh tempo utang-piutang.

### 6.10 Laporan Keuangan
- Laporan penjualan per cabang & konsolidasi seluruh cabang.
- Laporan utang-piutang (outstanding & jatuh tempo).
- Laporan stok (bahan baku & produk jadi).
- Laporan produksi.
- Export ke Excel & PDF.

### 6.11 Dashboard
- Dashboard Owner: ringkasan seluruh cabang (penjualan, stok kritis, utang-piutang jatuh tempo).
- Dashboard Kasir: ringkasan transaksi harian miliknya.
- Dashboard Superadmin: daftar tenant, status aktif/nonaktif.

### 6.12 Fitur Tambahan (Disepakati)
- Shift kasir (buka/tutup shift + rekonsiliasi kas).
- Retur pembelian (ke supplier) & retur penjualan (dari pembeli).
- Approval pembelian di atas nominal tertentu (opsional, fase lanjutan).

## 7. Non-Functional Requirements

- **PWA**: installable, app-shell caching, **tidak mendukung mode offline** — aplikasi mewajibkan koneksi internet aktif untuk seluruh transaksi.
- **Keamanan**: isolasi data antar tenant adalah persyaratan keras (hard requirement), bukan opsional.
- **Audit Trail**: seluruh aksi penting (transaksi, perubahan stok, aktivasi/nonaktivasi business) tercatat melalui activity log.
- **Skalabilitas**: single database dengan potensi jumlah tenant bertambah signifikan — struktur index dan query harus efisien per `business_id`.

## 8. Di Luar Cakupan (Out of Scope) Versi Ini

- Sistem subscription/billing/paket berbayar.
- Mode offline / sinkronisasi data.
- Superadmin impersonate akun tenant (dapat dipertimbangkan di fase lanjutan).
- API publik untuk integrasi pihak ketiga.

## 9. Dokumen Terkait

- `ARCHITECTURE.md` — detail teknis arsitektur multi-tenant & pemisahan area PWA/AdminLTE.
- `DATABASE.md` / `ERD.md` — skema database lengkap.
- `PERMISSIONS.md` — matrix akses detail per role.
- `BUSINESS-RULES.md` — logika kalkulasi produksi (BOM), FEFO, tax, diskon, utang-piutang.
- `AGENTS.md` — instruksi kerja untuk AI coding agent (opencode).
- `ROADMAP.md` — fase pengembangan.
