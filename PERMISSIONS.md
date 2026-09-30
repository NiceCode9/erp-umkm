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
| Tambah cabang baru | ✅ (satu-satunya yang bisa) | ❌ | ❌ |
| Ubah data/nonaktifkan cabang | ✅ | ✅ (cabang miliknya sendiri) | ❌ |
| Lihat data cabang sendiri | ❌ | ✅ (semua cabang miliknya) | ✅ (cabang sendiri saja) |
| **Manajemen User** | | | |
| Buat akun Owner baru untuk suatu business | ✅ (satu-satunya yang bisa) | ❌ | ❌ |
| Buat akun Kasir baru | ✅ (satu-satunya yang bisa) | ❌ | ❌ |
| Ubah data akun Kasir (nama, cabang, status aktif) | ✅ | ✅ (Kasir miliknya sendiri) | ❌ |
| Ubah/reset password akun Kasir | ✅ | ✅ (Kasir miliknya sendiri) | ❌ |
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
- **KEPUTUSAN FINAL (merevisi keputusan sebelumnya di atas): Superadmin adalah SATU-SATUNYA yang bisa menambah cabang baru dan membuat akun baru (Owner maupun Kasir) — Owner TIDAK BISA menambah cabang atau membuat akun Kasir baru sendiri.** Owner hanya bisa: mengedit data cabang yang sudah ada (nama, alamat, nonaktifkan), dan mengedit data akun Kasir yang sudah ada (nama, assignment cabang, status aktif) termasuk **mengubah/reset password Kasir**. Ini untuk menjaga provisioning struktur tenant (cabang & user) tetap terkontrol lewat satu pintu (Superadmin), sementara operasional harian (edit data, reset password) tetap praktis dilakukan Owner sendiri tanpa perlu menunggu Superadmin. Saat Superadmin menambah cabang/user untuk suatu business, tetap wajib pilih business context eksplisit (lihat `ARCHITECTURE.md` bagian 2.1).

## 4. Implementasi Teknis (Referensi untuk AGENTS.md)

- Permission granular dipetakan 1:1 dengan baris tabel di atas, misal: `manage-purchases`, `view-own-sales`, `view-all-sales`, `manage-production`, dst. Daftar kanonik dan pemetaan ke route tersedia di `database/seeders/RolePermissionSeeder.php`.
- Role `Superadmin`, `Owner`, `Kasir` di-assign permission-permission di atas melalui seeder (`RolePermissionSeeder`).
  - **Superadmin mendapat seluruh permission.** Area `/superadmin/*` dijaga middleware `role:Superadmin`, jadi permission adalah lapisan kedua — disimpan lengkap agar fitur baru tidak ikut 403.
  - **Owner sengaja TIDAK mendapat** `create-branches`, `create-kasir`, `delete-branches` (keputusan final bagian 3.1).
  - **Kasir sengaja TIDAK mendapat** `view-branches` — route `app.branches.*` berada di group `role:Owner` sehingga tidak pernah bisa diakses Kasir. Grant ini ditahan sampai fitur "lihat cabang sendiri" benar-benar ada.
- Permission yang dicabut (`manage-branches`, `manage-users`) dihapus dari tabel `permissions` oleh seeder lewat konstanta `RETIRED_PERMISSIONS`. **Jangan menambah permission yang tidak dipakai route/controller mana pun** — itu dead grant yang tidak menambah keamanan, hanya surface of confusion.
- Middleware/gate tambahan tetap diperlukan untuk validasi kepemilikan data (mis. Kasir hanya bisa lihat `sales` dengan `user_id = auth()->id()`), karena permission spatie hanya mengontrol akses fitur, bukan filter baris data.

## 5. Nama Permission Kanonik

Naming convention: **jamak** (`create-branches`, bukan `create-branch`), konsisten dengan seluruh permission lain di project. Modul: `businesses`, `branches`, `kasir`, `raw-materials`, `purchases`, `production`, `products`, `sales`, `shifts`, `shipments`, `reports`.

| Permission | Melayani route / aksi |
|---|---|
| `manage-businesses` | `/superadmin/businesses` (CRUD + aktivasi/nonaktivasi) |
| `view-superadmin-dashboard` | `/superadmin/dashboard` |
| `view-branches` | `GET /app/branches` (Owner) |
| `edit-branches` | `GET|PUT /app/branches/{branch}` (Owner) |
| `create-branches` | Hanya `/superadmin/businesses/{business}/branches` — route `/app/branches/create` mengembalikan **403** |
| `delete-branches` | Hanya Superadmin — route `DELETE /app/branches/{branch}` mengembalikan **403** |
| `edit-kasir` | `GET|PUT /app/kasir/{kasir}` (Owner) |
| `manage-kasir` | Menghapus/menonaktifkan Kasir (dipakai `UserPolicy`) |
| `reset-kasir-password` | `GET|POST /app/kasir/{kasir}/reset-password` (Owner) |
| `create-kasir` | Hanya `/superadmin/businesses/{business}/kasir` — route `/app/kasir/create` mengembalikan **403** |
| `edit-own-profile` | `/profile` |

### Route yang sengaja mengembalikan 403

Empat route berikut **dipertahankan** (bukan dihapus) agar akses langsung Owner menghasilkan 403 yang jelas, bukan 404 yang membingungkan. Semuanya adalah closure `abort(403)`, bukan form create:

- `GET /app/branches/create` dan `POST /app/branches`
- `GET /app/kasir/create` dan `POST /app/kasir`
- `DELETE /app/branches/{branch}`

## 6. Catatan Penting tentang `@can` di Blade

`spatie/laravel-permission` mendaftarkan `Gate::before` yang mengembalikan `true` **sebelum** policy dieksekusi. Akibatnya:

- `@can('edit-branches')` → dicek sebagai **permission**, policy tidak pernah dipanggil. Ini benar untuk route-level.
- `@can('resetPassword', $user)` → nama ability **bukan** nama permission, sehingga `Gate::before` mengembalikan `null` lalu **policy dieksekusi** → `business_id` ikut diperiksa. Ini yang benar untuk keputusan yang melibatkan objek tertentu.
- `@can('reset-kasir-password', $user)` → **JANGAN dipakai** untuk keputusan per-objek, karena tenancy check di `UserPolicy` akan dilewati.

Aturan praktis: pakai nama **permission** untuk gate di level fitur/route, dan nama **method policy** (`viewAny`, `view`, `update`, `delete`, `resetPassword`) saat gate bergantung pada objek tertentu.