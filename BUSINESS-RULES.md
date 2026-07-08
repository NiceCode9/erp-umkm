# BUSINESS-RULES.md - Logika Kalkulasi & Aturan Bisnis

Dokumen ini merinci logika yang WAJIB diimplementasikan secara konsisten via Service/Action class terpusat (lihat `AGENTS.md` bagian 5). Jangan duplikasi logika ini di banyak controller.

## 1. Produksi (BOM) & Pengurangan Stok Bahan Baku

### 1.1 Alur Umum
1. Owner membuat `production_order` dengan `product_id` dan `quantity_target`.
2. Sistem ambil semua baris `product_recipes` untuk `product_id` tersebut, beserta `products.recipe_yield_quantity` produk tersebut.
3. Untuk setiap baris resep, hitung total kebutuhan bahan baku:

   ```
   qty_per_unit_efektif = qty_per_batch / recipe_yield_quantity
   total_kebutuhan = qty_per_unit_efektif × quantity_target
   ```

   Contoh: resep menghasilkan 20 pcs (`recipe_yield_quantity = 20`) dengan kebutuhan tepung `qty_per_batch = 2000` gram. Maka `qty_per_unit_efektif = 100` gram/pcs. Jika `quantity_target = 50` pcs, `total_kebutuhan = 5000` gram tepung.

4. Konversi `total_kebutuhan` ke `base_unit` bahan baku jika satuan resep berbeda (lihat bagian 1.3).
5. Ambil batch bahan baku terkait di cabang tersebut, urutkan berdasarkan `expired_date ASC` (FEFO — batch yang lebih cepat kedaluwarsa dipakai lebih dulu; batch tanpa `expired_date` diperlakukan sebagai prioritas terakhir).
6. Kurangi `quantity_remaining` pada batch secara berurutan sampai `total_kebutuhan` terpenuhi. Jika satu batch tidak cukup, lanjut ke batch berikutnya.
7. Catat setiap pengurangan per batch ke `production_consumptions`.
8. Catat pergerakan stok keluar ke `stock_movements` (`reference_type = production`, `movement_type = out`).
9. **Buat `production_code` otomatis** untuk production order ini (format `PRD-{YYYYMMDD}-{urutan 4 digit per hari}`, unique, immutable).
10. Tambahkan hasil produksi (`quantity_target`, atau `quantity_actual` jika berbeda dari target karena kendala stok) sebagai **`product_batches` BARU** di cabang terkait — field `production_order_id`, `production_code` (denormalisasi), `expired_date` (dari input Owner di production order, boleh null), `produced_at`. Catat sebagai `movement_type = in` di `stock_movements` (`reference_type = production`, `batch_id` merujuk ke `product_batches` yang baru dibuat ini) — label tampilan pergerakan ini adalah **"Produksi ({production_code})"**, mis. "Produksi (PRD-20260708-0001)".

### 1.2 Validasi Stok Tidak Cukup
- Jika total stok bahan baku (across semua batch di cabang tersebut) tidak mencukupi `total_kebutuhan`, **tolak** production order (status tetap `draft`, tidak boleh partial-deduct tanpa persetujuan eksplisit).
- Tampilkan ke Owner: bahan baku mana yang kurang, dan berapa selisihnya.

### 1.3 Konversi Satuan
- Jika satuan pada `product_recipes.unit` berbeda dari `raw_materials.base_unit` (mis. resep pakai gram, stok pakai kg), WAJIB dikonversi sebelum pengurangan stok.
- Fase awal: cukup dukung konversi sederhana antar satuan berat (g↔kg) dan volume (ml↔liter) via tabel konversi tetap di kode/config.
- Fase lanjutan (opsional, dicatat di `ROADMAP.md`): tabel `unit_conversions` dinamis bila kombinasi satuan makin kompleks.

### 1.4 Di Luar Cakupan Versi Ini
- Persentase susut/waste produksi otomatis.
- Versioning resep (riwayat perubahan komposisi resep dari waktu ke waktu).
- Kedua hal ini masuk fase lanjutan — production order tetap harus mencatat `product_recipes` yang dipakai saat itu sebagai referensi historis minimal (simpan snapshot ringkas bila memungkinkan).

## 2. FEFO (First Expired First Out)

- **Berlaku untuk KEDUA jenis batch**: `raw_material_batches` (bahan baku) DAN `product_batches` (produk jadi) — bukan cuma bahan baku.
- Konteks penerapan:
  - Bahan baku: produksi (konsumsi resep), retur pembelian, stok opname yang mengoreksi ke bawah, distribusi antar cabang (bagian 7.1).
  - Produk jadi: **penjualan** (lihat bagian baru di dokumen Fase Penjualan — setiap `sale_item` mengambil dari `product_batches` mengikuti FEFO, tercatat di `sale_item_batches`), retur penjualan, stok opname, distribusi antar cabang (bagian 7.1).
- Urutan pengambilan: `expired_date ASC NULLS LAST` — batch tanpa tanggal kedaluwarsa diambil paling akhir. Aturan ini SAMA PERSIS untuk kedua jenis batch.
- Setiap pembelian bahan baku baru **selalu membuat batch baru** (tidak digabung ke batch lama), meskipun bahan baku dan supplier sama, kecuali `expired_date` dan `purchase_price` identik dengan batch yang sudah ada dan belum digunakan sama sekali.
- Setiap production order **selalu membuat `product_batches` baru** (satu production order = satu batch produk baru), tidak pernah digabung ke batch produk lain meski produk & tanggal produksi sama — supaya `production_code` tetap 1:1 dengan asal produksinya untuk keperluan traceability.

## 2.1 Konsumsi FEFO saat Penjualan (Produk Jadi)

> Detail lengkap alur penjualan ada di dokumen Fase Penjualan — bagian ini hanya menetapkan aturan FEFO-nya agar konsisten sejak awal.

- Saat `sale_item` dibuat (checkout kasir), sistem mengambil `product_batches` produk tersebut di cabang yang sama, urutkan `expired_date ASC NULLS LAST` (sama seperti bahan baku).
- Kurangi `quantity_remaining` batch demi batch sampai `sale_item.quantity` terpenuhi. Jika satu batch tidak cukup, lanjut ke batch berikutnya. Catat tiap pengambilan ke `sale_item_batches`.
- Jika total stok produk (across semua batch di cabang itu) tidak cukup untuk memenuhi keranjang kasir, **tolak checkout** untuk item tersebut (tampilkan stok tersedia ke Kasir) — sama seperti validasi stok produksi di bagian 1.2, tidak boleh menjual melebihi stok yang ada.
- Catat `stock_movements` (`movement_type = out`, `reference_type = sale`, `batch_id` merujuk ke `product_batches` yang terpakai).

## 3. Diskon

- Diskon berlaku **per transaksi penjualan** (bukan per item).
- Tipe: `nominal` atau `percent` (kolom `discount_type`, `discount_value` di `sales`).
- Kalkulasi:

  ```
  jika discount_type = 'percent':
      discount_amount = subtotal × (discount_value / 100)
  jika discount_type = 'nominal':
      discount_amount = discount_value
  ```

- `discount_amount` tidak boleh melebihi `subtotal` (validasi wajib, minimal total setelah diskon = 0).

## 4. Tax

- Setting tax dikelola per cabang di `branch_settings` (`tax_enabled`, `tax_percentage`).
- Saat transaksi penjualan dibuat, sistem membaca setting cabang **pada saat itu** dan menyimpannya sebagai snapshot di `sales.tax_percentage_applied` — perubahan setting di kemudian hari TIDAK mempengaruhi transaksi yang sudah tercatat.
- Kalkulasi:

  ```
  dasar_pengenaan_tax = subtotal - discount_amount
  jika tax_enabled = true:
      tax_amount = dasar_pengenaan_tax × (tax_percentage / 100)
  jika tax_enabled = false:
      tax_amount = 0
  ```

- Urutan kalkulasi total transaksi:

  ```
  subtotal = SUM(sale_items.subtotal)
  discount_amount = (lihat bagian 3)
  dasar_pengenaan_tax = subtotal - discount_amount
  tax_amount = (lihat rumus di atas)
  total_amount = dasar_pengenaan_tax + tax_amount
  ```

## 5. Utang ke Supplier (dari Pembelian)

- Setiap `purchase` dengan `payment_status != 'paid'` dianggap punya utang outstanding sebesar `total_amount - SUM(purchase_payments.amount)`.
- Pembayaran cicilan dicatat di `purchase_payments`; setelah setiap pembayaran, `payment_status` di-update otomatis:
  - `unpaid` jika belum ada pembayaran sama sekali.
  - `partial` jika sudah ada pembayaran tapi belum lunas.
  - `paid` jika total pembayaran ≥ `total_amount`.

## 6. Piutang dari Pembeli (dari Penjualan)

- Berlaku logika yang sama seperti utang supplier, tapi pada tabel `sales` dan `sale_payments`.
- Piutang outstanding = `total_amount - SUM(sale_payments.amount)`.
- Kasir dapat mencatat pembayaran cicilan piutang untuk transaksi di cabangnya sendiri (lihat `PERMISSIONS.md`).
- Notifikasi jatuh tempo (fase lanjutan): perlu kolom `due_date` di `sales` untuk transaksi non-tunai, dipakai sebagai basis reminder.

## 7. Pengiriman ke Pembeli (Ecer vs Borongan)

- `shipments.type = 'ecer'` — pengiriman satuan kecil ke pembeli, biasanya tanpa keterkaitan langsung ke satu `sale_id` besar (bisa multiple pengiriman kecil).
- `shipments.type = 'borongan'` — pengiriman jumlah besar ke pembeli, umumnya terkait satu `sale_id` dengan volume besar.
- Pengiriman barang terjual (`sale_id` terisi) tidak mengubah stok lagi (stok sudah dikurangi saat transaksi penjualan dibuat) — `shipments` di kasus ini murni pencatatan status logistik (pending/shipped/delivered).
- Modul `shipments` khusus untuk pengiriman **ke pembeli**, terpisah dari distribusi stok antar cabang (lihat bagian 7.1).

## 7.1 Distribusi Stok Antar Cabang

- Dipicu oleh Owner (lintas cabang miliknya sendiri) melalui `stock_distributions`, terpisah dari `shipments` (yang khusus pengiriman ke pembeli).
- Dapat mencakup bahan baku maupun produk jadi (`stock_distribution_items.item_type`) — **KEDUANYA punya batch** (`raw_material_batches` / `product_batches`) dan KEDUANYA mengikuti FEFO saat dipilih untuk dipindahkan (bagian 2).
- Alur status: `pending` → `shipped` → `received`.
- **Saat status berubah ke `shipped`**: kurangi stok di `origin_branch_id` (catat `stock_movements` `movement_type = out`, `reference_type = stock_distribution`). Pilih batch (bahan baku ATAU produk jadi) mengikuti FEFO (bagian 2), catat rincian batch yang dipindahkan ke `stock_distribution_item_batches`.
- **Saat status berubah ke `received`**: tambahkan stok di `destination_branch_id` (catat `stock_movements` `movement_type = in`, `reference_type = stock_distribution`). Buat entri batch BARU di cabang tujuan (baik `raw_material_batches` maupun `product_batches`, tergantung jenis item) dengan `expired_date` mengikuti batch asal (dan `purchase_price`/`production_code` ikut diturunkan sesuai jenisnya), agar FEFO & traceability tetap valid di cabang tujuan.
- Stok dalam status `shipped` (sudah keluar dari asal, belum diterima tujuan) dianggap "dalam perjalanan" — tidak tersedia untuk dijual/produksi di cabang manapun sampai `received`.
- Hanya Owner yang dapat menginisiasi dan mengonfirmasi penerimaan distribusi (Kasir tidak punya akses ke modul ini, lihat `PERMISSIONS.md`).

## 8. Stok Opname

- `stock_opnames.difference = actual_quantity - system_quantity`.
- Jika `difference` negatif → catat `stock_movements` dengan `movement_type = out`, `reference_type = stock_opname`.
- Jika `difference` positif → catat `stock_movements` dengan `movement_type = in`, `reference_type = stock_opname`.
- Untuk bahan baku dengan batch, opname sebaiknya dilakukan per batch (`batch_id` terisi) agar FEFO tetap akurat setelah koreksi.

## 9. Shift Kasir & Rekonsiliasi Kas

- `closing_cash_system` dihitung otomatis dari total pembayaran tunai pada `sales` yang terjadi dalam rentang `cashier_shift_id` tersebut.
- `difference = closing_cash_actual - closing_cash_system`.
- Selisih signifikan (threshold ditentukan kemudian) dapat memicu flag untuk ditinjau Owner.
