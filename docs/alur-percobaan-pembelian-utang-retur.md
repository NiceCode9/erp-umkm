# Alur Teknis Percobaan: Pembelian → Utang Supplier → Retur Pembelian

Dokumen ini adalah **runbook pengujian manual** untuk memverifikasi perbaikan
BUG-1 s.d. BUG-13 pada alur pembelian, utang supplier, dan retur pembelian.

Rujukan aturan bisnis: [`../BUSINESS-RULES.md`](../BUSINESS-RULES.md) bagian 5 & 5.1.
Skema database: [`../DATABASE.md`](../DATABASE.md) bagian 2 & 4.

---

## 1. Ringkasan Apa yang Diperbaiki

| # | Bug lama | Gejala yang bisa dilihat user | Status |
|---|---|---|---|
| B1 | `purchase_returns` tidak punya kolom `total_amount` | Retur pembelian **tidak pernah** mengurangi utang supplier. `SUM` = 0 senyap, tanpa error | ✅ Diperbaiki |
| B2 | `raw_material_batches` tidak punya `business_id` | Owner bisa mengurangi stok batch **milik tenant lain** lewat form retur | ✅ Diperbaiki |
| B3 | Validasi `exists:` telanjang | Pembelian bisa menunjuk supplier & bahan baku tenant lain | ✅ Diperbaiki |
| B4 | `purchase_return_items` tanpa `purchase_item_id`, harga dari input user | Retur melebihi pembelian tidak bisa dicegah; nilai retur bisa dimanipulasi | ✅ Diperbaiki |
| B5 | 7 salinan formula utang berbeda | Halaman utang & laporan utang bisa/show angka berbeda | ✅ Diperbaiki |
| B6 | Tidak ada `lockForUpdate()` | Dua retur bersamaan bisa membuat stok batch negatif | ✅ Diperbaiki |
| B7 | Tidak ada guard overpayment | Bayar melebihi utang diterima, kelebihannya hilang dari UI | ✅ Diperbaiki |
| B8 | Nol test coverage | — | ✅ 18 test |
| B9 | 5 permission tidak pernah ditegakkan | Permission pembelian = konfigurasi mati | ✅ Diperbaiki |
| B10 | Field name form retur duplikat | Hanya bisa retur **satu batch per baris**, diam-diam | ✅ Diperbaiki |
| B11 | Export PDF utang | Tombol PDF → **HTTP 500** | ✅ Diperbaiki |
| B12 | Kolom export "Sudah Dibayar" | Nilai retur tersamar sebagai pembayaran | ✅ Diperbaiki |
| B13 | Tidak ada snapshot diskon/pajak pembelian | Pembelian kena pajak tidak bisa dicatat | ✅ Diperbaiki |

**Rumus final (satu-satunya sumber kebenaran):**

```
outstanding = total_amount − SUM(purchase_payments.amount) − SUM(purchase_returns.total_amount)
```

`outstanding` **boleh negatif** → status `credit` (retur melebihi sisa utang, supplier owes kita).

| Status | Kondisi | Tampil di |
|---|---|---|
| `unpaid` | `outstanding > 0`, belum ada bayar | Section "Utang Outstanding" |
| `partial` | `outstanding > 0`, sudah sebagian bayar | Section "Utang Outstanding" |
| `paid` | `outstanding == 0` | Tidak tampil di halaman utang |
| `credit` | `outstanding < 0` | **Section "Kredit / Klaim ke Supplier"** |

---

## 2. Persiapan

### 2.1 Backup (WAJIB)

Migration sudah mengubah skema dan melakukan backfill. Backup dulu:

```bash
php artisan backup:run          # jika sudah dikonfigurasi
# atau manual:
mysqldump -u root -p erp > erp_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2.2 Jalankan Migration

```bash
php artisan migrate --force
```

Harus muncul 5 migration berikut, semuanya `DONE`:

```
2026_10_01_010000_add_business_id_to_raw_material_batches
2026_10_01_020000_add_total_amount_to_purchase_returns
2026_10_01_030000_add_purchase_item_id_to_purchase_return_items
2026_10_01_040000_add_credit_status_to_purchases
2026_10_01_050000_add_discount_and_tax_snapshot_to_purchases
```

### 2.3 Verifikasi Backfill

`raw_material_batches.business_id` di-backfill dari `raw_materials.business_id`.
Pastikan **tidak ada** baris NULL atau orphan:

```sql
-- harus mengembalikan 0
SELECT COUNT(*) FROM raw_material_batches WHERE business_id IS NULL;

-- harus mengembalikan 0
SELECT COUNT(*) FROM raw_material_batches b
LEFT JOIN raw_materials r ON r.id = b.raw_material_id
WHERE r.id IS NULL;

-- harus mengembalikan 0
SELECT COUNT(*) FROM purchase_returns WHERE total_amount IS NULL;
```

Verifikasi enum & kolom baru:

```sql
SHOW COLUMNS FROM purchases LIKE 'payment_status';
-- harus: enum('unpaid','partial','paid','credit')   <-- ada 'credit'

SHOW COLUMNS FROM raw_material_batches LIKE 'business_id';
-- Null: NO   (sudah NOT NULL)

SHOW COLUMNS FROM purchase_return_items LIKE 'purchase_item_id';
-- harus ada
```

### 2.4 Isi Data Awal

Pastikan ada:
- 1 business aktif, dengan **minimal 2 cabang** (cabang A & B)
- 1 akun **Owner** (login manual)
- Minimal 1 supplier, minimal 1 bahan baku dengan `minimum_stock` 0
- Pengaturan pajak salah satu cabang: aktifkan `tax_enabled` + `tax_percentage`

Untuk uji cross-tenant (bagian 5), siapkan business kedua + Owner kedua + bahan baku + batch milik tenant itu.

---

## 3. Percobaan Fungsional — Alur Normal

### 3.1 Catat pembelian

1. Login sebagai Owner.
2. Buka **Pembelian → Pembelian Baru**.
3. Isi: Cabang A, Supplier, No. Invoice `T-001`, Tanggal.
4. Tambah 1 item: Bahan Baku X, qty **10**, harga **10.000**, batch `T-001-A`, expired date kosong.
5. Klik **Simpan**.

**Verifikasi:**
- [ ] Redirect ke daftar pembelian, flash "berhasil dicatat".
- [ ] Total = **Rp 100.000** (10 × 10.000).
- [ ] Kolom **Dibayar** = Rp 0, **Sisa Utang** = Rp 100.000, status **Belum**.
- [ ] Buka **Bahan Baku → X → Detail**: batch `T-001-A` muncul, sisa **10**.

### 3.2 Bayar sebagian

1. Dari daftar pembelian, buka detail `T-001` → **Bayar**.
2. Masukkan **60.000**, metode Tunai.
3. Simpan.

**Verifikasi:**
- [ ] Status berubah jadi **Sebagian**.
- [ ] Sisa Utang = **Rp 40.000**.
- [ ] Di **Utang Supplier**, `T-001` ada di section "Utang Outstanding" dengan sisa Rp 40.000.

### 3.3 Retur sebagian

1. Dari detail `T-001` → **Retur**.
2. Tanggal retur = hari ini, alasan "Rusak".
3. Pada baris batch `T-001-A`, isi qty **2,5**.
4. Simpan.

**Verifikasi:**
- [ ] Flash menyebut nilai retur: **Rp 25.000**.
- [ ] Sisa stok batch `T-001-A` jadi **7,5** (bukan 10).
- [ ] Status pembelian tetap **Sebagian** (outstanding 100.000 − 60.000 − 25.000 = **15.000**).
- [ ] Sisa Utang di detail = **Rp 15.000**.

> **Ini inti perbaikan B1.** Sebelum diperbaiki, Sisa Utang tetap Rp 40.000.

### 3.4 Bayar pelunasan

1. Detail `T-001` → **Bayar** → masukkan **15.000**.
2. Simpan.

**Verifikasi:**
- [ ] Status **Lunas**.
- [ ] `T-001` **hilang** dari halaman Utang Supplier.
- [ ] Halaman Utang menampilkan "Semua utang supplier sudah lunas."

### 3.5 Retur > sisa utang (memicu status `credit`)

1. Catat pembelian baru `T-002`: 10 × 10.000 = **Rp 100.000**.
2. Bayar **lunas** 100.000 → status **Lunas**.
3. Retur **4** unit dari `T-002` → nilai retur **Rp 40.000**.

**Verifikasi:**
- [ ] Status berubah jadi **Kredit ke Supplier** (bukan "Lunas").
- [ ] Di detail: Sisa Utang tampil **Rp -40.000** (berwarna merah) + keterangan "kredit — supplier owes kita".
- [ ] Di **Utang Supplier**, `T-002` muncul di section **"Kredit / Klaim ke Supplier"** dengan nilai **Rp 40.000**.
- [ ] Card "Kredit ke Supplier" di dashboard Owner menampilkan `T-002`.
- [ ] Sisa stok batch berkurang menjadi **6**.

> **Ini perbaikan atas cacat turunan dari B1.** Sebelum `credit` ada, pembelian lunas yang diretur **sembunyi sepenuhnya** dari semua laporan.

### 3.6 Diskon & pajak (B13)

1. Aktifkan pajak 10% di pengaturan Cabang A.
2. Catat pembelian `T-003`: 10 × 10.000, diskon tipe **Nominal** nilai **20.000**.
3. Simpan.

**Verifikasi:**
- [ ] Detail `T-003` menampilkan: Subtotal 100.000 · Diskon 20.000 · Pajak 10.000 · **Total 90.000**.
- [ ] Urutan perhitungan benar: `(100.000 − 20.000) × 10% = 10.000`, total `80.000 + 10.000 = 90.000`.
- [ ] Ubah `tax_percentage` Cabang A jadi 20% → **buka lagi `T-003`**. Total **tetap 90.000** (snapshot, lihat `AGENTS.md` §6).

---

## 4. Percobaan Validasi & Keamanan

### 4.1 Overpayment ditolak (B7)

1. Detail pembelian `T-004` (belum bayar) → **Bayar**.
2. Masukkan angka **melebihi** sisa utang, misal 999.999.
3. Simpan.

**Verifikasi:**
- [ ] Muncul error inline "Pembayaran melebihi sisa utang".
- [ ] **Tidak ada** baris baru di `purchase_payments`.
- [ ] Tombol submit nonaktif otomatis kalau utang sudah 0.

### 4.2 Retur melebihi yang dibeli (B4)

1. Detail pembelian dengan qty 10 → Retur.
2. Isi qty **999**.

**Verifikasi:**
- [ ] Error "Kuantitas retur melebihi sisa yang dapat diretur (10)".
- [ ] Tidak ada `purchase_returns` baru.

### 4.3 Retur kumulatif (B4)

1. Retur **6** dari pembelian qty 10 → berhasil.
2. Retur **6** lagi (sisa seharusnya 4).

**Verifikasi:**
- [ ] Percobaan kedua **DITOLAK** dengan pesan menyebut sisa **4**.
- [ ] Total retur kumulatif tidak pernah melebihi 10.

### 4.4 Harga retur tidak bisa dimanipulasi (B4)

1. Inspect HTML form retur (View Source / DevTools).
2. **Harga tidak lagi bisa diedit** — ditampilkan sebagai teks read-only.
3. Kirim request langsung ke server dengan `unit_price=1` (DevTools → edit & resend, atau Postman):

```
POST /app/production/../app/purchases/{id}/return
items[0][unit_price] = 1
```

**Verifikasi:**
- [ ] Response sukses, TAPI `purchase_return_items.unit_price` = **10.000** (harga asli dari `PurchaseItem`), bukan 1.
- [ ] `subtotal` = qty × 10.000.

Bisa diperiksa langsung:
```sql
SELECT quantity, unit_price, subtotal FROM purchase_return_items ORDER BY id DESC LIMIT 5;
```

### 4.5 Isolasi cross-tenant (B2 & B3) — WAJIB

> Butuh 2 business + 2 Owner. Ini bagian **paling penting** untuk diuji karena sebelumnya kerentanan ini bisa dieksploitasi.

#### 4.5.1 Batch milik tenant lain (B2)

1. Business A & B dibuat. Catat pembelian di **kedua** business → muncul 2 batch.
2. Login sebagai **Owner A**.
3. Dari pembelian milik **A**, buka form Retur.
4. **Inspect** HTML: cari `raw_material_batch_id`. Ganti nilainya dengan **id batch milik B**.
5. Submit.

**Verifikasi:**
- [ ] Error "Batch tidak valid untuk bahan baku/cabang pembelian ini".
- [ ] **Stok batch B tidak berubah** sama sekali.
- [ ] **Tidak ada** `stock_movements` baru untuk tenant B:
```sql
SELECT COUNT(*) FROM stock_movements
WHERE business_id = <id_business_B> AND reference_type = 'purchase_return';
```

#### 4.5.2 Supplier milik tenant lain (B3)

1. Login sebagai **Owner A**. Catat pembelian baru.
2. Ubah `supplier_id` (hidden/DevTools) dengan id supplier milik **B**.

**Verifikasi:**
- [ ] Error validasi pada `supplier_id`.
- [ ] Tidak ada `purchases` baru.

#### 4.5.3 Bahan baku milik tenant lain (B3)

1. Ubah `items[0][raw_material_id]` dengan id bahan baku milik **B**.

**Verifikasi:**
- [ ] Error validasi pada `items.0.raw_material_id`.
- [ ] Tidak ada `raw_material_batches` baru sama sekali.

#### 4.5.4 Halaman utang tidak bocor (B5)

1. Login sebagai **Owner A** → buka **Utang Supplier**.

**Verifikasi:**
- [ ] Tidak ada invoice / supplier milik **B** di halaman mana pun.
- [ ] Card total utang & kredit hanya berisi angka tenant A.

---

## 5. Percobaan yang SUDAH Otomatis (Pest)

Tidak perlu dijalankan manual, tapi berguna untuk memastikan tidak ada regresi:

```bash
php artisan test --filter=PurchaseDebtTest
php artisan test
```

| # | Nama test | Menutup |
|---|---|---|
| 1 | retur pembelian mengurangi utang supplier | B1 |
| 2 | retur lebih besar dari sisa utang menghasilkan status credit | B1 |
| 3 | halaman utang memisahkan utang dan kredit | B1, B5 |
| 4 | pembelian tidak bisa memakai supplier milik business lain | B3 |
| 5 | pembelian tidak bisa memakai bahan baku milik business lain | B3 |
| 6 | batch bahan baku memiliki business_id yang benar | B2 |
| 7 | retur tidak bisa menyentuh batch milik business lain | B2 |
| 8 | retur tidak bisa melebihi kuantitas yang dibeli | B4 |
| 9 | retur kumulatif tidak boleh melebihi kuantitas yang dibeli | B4 |
| 10 | harga retur diambil dari baris pembelian bukan dari input user | B4 |
| 11 | retur menyimpan purchase_item_id untuk jejak audit | B4 |
| 12 | pembayaran melebihi sisa utang ditolak | B7 |
| 13 | status pembayaran dihitung PurchaseService dengan benar | B5 |
| 14 | unpaid kembali menjadi unpaid saat tidak ada pembayaran | B5 |
| 15 | decreaseRawMaterialStockFromBatch menolak melebihi stok | B6 |
| 16 | decreaseRawMaterialStockFromBatch menolak batch milik business lain | B6 |
| 17 | pembelian menyimpan snapshot diskon dan pajak | B13 |
| 18 | route pembayaran dan retur memakai PurchasePolicy | B9 |

---

## 6. Percobaan Export (B11 & B12)

1. Buka **Laporan → Utang & Piutang**.
2. Klik **Excel** → file `.xlsx` terunduh.
3. Klik **PDF** → file `.pdf` terunduh (**sebelumnya HTTP 500**).

**Verifikasi Excel — kolom harus terpisah:**

| Invoice | Supplier | Cabang | Tanggal | Total | Sudah Dibayar | Retur | Sisa Utang | Status |
|---|---|---|---|---|---|---|---|---|
| T-001 | ... | ... | ... | 100000 | 60000 | 25000 | 15000 | partial |

- [ ] Ada **9 kolom** (kolom "Retur" terpisah dari "Sudah Dibayar").
- [ ] `Retur` = 25.000 untuk `T-001`.
- [ ] `Sisa Utang` = 15.000.

**Verifikasi PDF:**
- [ ] Berisi section "Utang Outstanding" dengan baris tabel.
- [ ] Bila ada kredit, ada section "Kredit / Klaim ke Supplier".
- [ ] Ada baris `TOTAL` di footer setiap tabel.

---

## 7. Verifikasi Konsistensi Angka (B5)

Formula yang sama harus dipakai **semua** tampilan. Cek 5 tempat ini menghasilkan **angka identik** untuk pembelian yang sama:

| Layar | Akses |
|---|---|
| Daftar Pembelian | `/app/purchases` → kolom "Sisa Utang" |
| Detail Pembelian | `/app/purchases/{id}` → "Sisa Utang" |
| Utang Supplier | `/app/debts` → kolom "Sisa Utang" |
| Dashboard Owner | card "Total Utang Supplier" |
| Laporan Utang | `/app/reports/debts` → kolom "Sisa" |
| Export Excel | kolom "Sisa Utang" |

**Verifikasi:**
- [ ] Semua menunjukkan angka sama untuk `T-001` (harus **15.000** setelah skenario 3.1–3.3).

> **Sebelum diperbaiki**, halaman "Utang Supplier" dan "Laporan Utang" memakai dua formula berbeda dan bisa menampilkan angka berbeda. Ini yang dipakai test #3 & #13.

---

## 8. Daftar Periksa Rollback

Kalau perlu membatalkan:

```bash
php artisan migrate:rollback --step=5     # 5 migration baru
```

⚠️ `down()` migration `2026_10_01_040000` mengubah baris ber-`credit` menjadi `paid` lebih dulu, lalu mengembalikan enum ke 3 nilai. Kolom `subtotal`/`discount_*`/`tax_*` **ikut terhapus** — pastikan tidak ada data diskon/pajak yang perlu disimpan sebelum rollback.

`raw_material_batches.business_id` **tidak bisa dihapus** tanpa kehilangan informasi tenant batch. Kalau sudah terlanjur dipakai, lebih aman forward-fix daripada rollback.

---

## 9. ⚠️ Risiko yang Diketahui (belum diperbaiki)

**`product_batches` belum punya `business_id` dan belum punya global scope.**

Ini lubang cross-tenant **di jalur penjualan** yang kelasnya sama dengan B2, tetapi berada di luar cakupan perbaikan ini:

- `StockService::consumeProductStockForSale()` masih bisa dipanggil dengan `product_id` milik tenant lain.
- `adjustStockFromOpname()` (`StockService`) punya masalah serupa untuk batch produk.

Dokumentasi di `BUSINESS-RULES.md` bagian 5.3 dan change log `AGENTS.md` bagian 10.
Disarankan sebagai pekerjaan berikutnya dengan pola yang sama seperti B2.
