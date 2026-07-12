# PERMISSIONS.md - Matrix Akses per Role

Dikelola menggunakan `spatie/laravel-permission`. Tiga role utama: **Superadmin**, **Owner**, **Kasir**. Tidak ada role tambahan tanpa konfirmasi eksplisit (lihat `AGENTS.md` bagian 8).

## 1. Prinsip Umum

- **Superadmin**: `business_id = null`, akses lintas tenant, hanya area `/superadmin/*`.
- **Owner**: `business_id` terisi, `branch_id = null` → akses semua cabang miliknya sendiri.
- **Kasir**: `business_id` terisi, `branch_id` wajib terisi → akses terbatas pada satu cabang.
- Semua permission Owner & Kasir otomatis ter-scope oleh Global Scope `business_id` (lihat `AGENTS.md`); matrix di bawah adalah lapisan tambahan di atas scoping tersebut (fitur mana yang boleh diakses, bukan hanya data mana).

## 2. Matrix Akses per Modul

| Modul / Aksi | Superadmin | Owner | Kasir |
|---|:---:|:---:|:---:|
| **Manajemen Business (Tenant)** | | | |
| Lihat daftar business | ✅ | ❌ | ❌ |
| Aktifkan/nonaktifkan business | ✅ | ❌ | ❌ |
| **Manajemen Cabang** | | | |
| Tambah/ubah/nonaktifkan cabang | ❌ | ✅ | ❌ |
| Lihat data cabang sendiri | ❌ | ✅ (semua cabang miliknya) | ✅ (cabang sendiri saja) |
| **Manajemen User** | | | |
| Buat/ubah akun Kasir | ❌ | ✅ | ❌ |
| Ubah profil sendiri | ❌ | ✅ | ✅ |
| **Bahan Baku & Stok** | | | |
| Tambah/ubah master bahan baku | ❌ | ✅ | ❌ |
| Lihat stok bahan baku | ❌ | ✅ | ❌ |
| Stok opname | ❌ | ✅ | ❌ |
| **Pembelian** | | | |
| Input transaksi pembelian | ❌ | ✅ | ❌ |
| Lihat riwayat pembelian | ❌ | ✅ | ❌ |
| Bayar cicilan utang ke supplier | ❌ | ✅ | ❌ |
| Retur pembelian | ❌ | ✅ | ❌ |
| **Produksi** | | | |
| Kelola resep (BOM) | ❌ | ✅ | ❌ |
| Jalankan production order | ❌ | ✅ | ❌ |
| Lihat riwayat produksi | ❌ | ✅ | ❌ |
| **Produk & Harga** | | | |
| Tambah/ubah master produk | ❌ | ✅ | ❌ |
| Atur harga jual & satuan | ❌ | ✅ | ❌ |
| **Penjualan (Kasir)** | | | |
| Input transaksi penjualan | ❌ | ❌ | ✅ |
| Lihat riwayat penjualan **miliknya sendiri** | ❌ | ❌ | ✅ |
| Lihat riwayat penjualan **semua kasir/cabang** | ❌ | ✅ | ❌ |
| Terima pembayaran cicilan piutang | ❌ | ✅ | ✅ (untuk transaksi di cabangnya) |
| Retur penjualan | ❌ | ✅ | ❌ |
| **Shift Kasir** | | | |
| Buka/tutup shift | ❌ | ❌ | ✅ |
| Lihat rekap shift semua kasir | ❌ | ✅ | ❌ |
| **Pengiriman** | | | |
| Input pengiriman (dari transaksi miliknya sendiri) | ❌ | ✅ | ✅ (transaksi di cabangnya) |
| Kelola/lihat SEMUA pengiriman lintas cabang & kasir | ❌ | ✅ | ❌ |
| **Utang Piutang** | | | |
| Lihat & kelola utang ke supplier | ❌ | ✅ | ❌ |
| Lihat & kelola piutang dari pembeli | ❌ | ✅ | ❌ |
| **Laporan Keuangan** | | | |
| Lihat laporan (semua jenis) | ❌ | ✅ | ❌ |
| Export Excel/PDF | ❌ | ✅ | ❌ |
| **Setting** | | | |
| Atur tax on/off per cabang | ❌ | ✅ | ❌ |
| **Dashboard** | | | |
| Dashboard Superadmin (daftar tenant) | ✅ | ❌ | ❌ |
| Dashboard Owner (semua cabang) | ❌ | ✅ | ❌ |
| Dashboard Kasir (harian, cabang sendiri) | ❌ | ❌ | ✅ |

## 3. Hal yang Perlu Dikonfirmasi

- **Kasir lintas cabang**: PRD saat ini mengasumsikan satu Kasir = satu cabang. Jika ke depan ada kebutuhan kasir yang bisa pindah-pindah cabang (misal shift di cabang berbeda), perlu penyesuaian skema `branch_id` di `users` menjadi relasi many-to-many.

## 3.1 Keputusan Terkonfirmasi

- Kasir **boleh** menerima dan mencatat pembayaran cicilan piutang pelanggan, terbatas pada transaksi di cabangnya sendiri.
- **Tidak ada self-registration.** Akun hanya dibuat melalui: Superadmin membuat business + akun Owner awal sekaligus; Owner membuat akun Kasir. Tidak ada role yang bisa mendaftar sendiri lewat halaman publik (lihat `AGENTS.md` bagian 3.1).
- **Kasir boleh input pengiriman** untuk transaksi penjualan miliknya sendiri di cabangnya (bukan cuma Owner) — lihat `BUSINESS-RULES.md` bagian 7 untuk alur lengkapnya. Kasir TIDAK bisa melihat/kelola pengiriman lintas cabang atau kasir lain.

## 4. Implementasi Teknis (Referensi untuk AGENTS.md)

- Permission granular sebaiknya dipetakan 1:1 dengan baris tabel di atas, misal: `manage-purchases`, `view-own-sales`, `view-all-sales`, `manage-production`, dst.
- Role `Superadmin`, `Owner`, `Kasir` di-assign permission-permission di atas melalui seeder (`RolePermissionSeeder`).
- Middleware/gate tambahan tetap diperlukan untuk validasi kepemilikan data (mis. Kasir hanya bisa lihat `sales` dengan `user_id = auth()->id()`), karena permission spatie hanya mengontrol akses fitur, bukan filter baris data.
