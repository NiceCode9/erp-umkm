# DATABASE.md - Skema Database & ERD

> Konvensi: semua tabel pakai `id` (bigint, auto increment) sebagai primary key kecuali disebutkan lain. Semua tabel punya `created_at`, `updated_at` (dan `deleted_at` bila soft delete dianjurkan). Kolom `business_id` WAJIB ada di semua tabel yang datanya spesifik ke satu tenant — lihat aturan scoping di `AGENTS.md`.

## 1. Tenant & Struktur Organisasi

### `businesses`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string | Nama UMKM |
| owner_name | string | Nama pemilik (referensi cepat, user aslinya tetap di tabel `users`) |
| phone | string | |
| address | text | |
| is_active | boolean, default true | Dikontrol Superadmin |
| deactivated_at | timestamp, nullable | |
| deactivated_by | FK users (Superadmin), nullable | |

### `branches` (Cabang)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| name | string | |
| address | text | |
| is_active | boolean, default true | |

### `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses, **nullable** | Null untuk Superadmin (lintas tenant). **PENTING:** jangan terapkan Global Scope generik ke model User berdasarkan kolom ini — lihat `AGENTS.md` bagian 2.1 (risiko infinite recursion). Filter per business untuk User dilakukan eksplisit di query/controller. |
| branch_id | FK branches, nullable | Wajib diisi untuk role Kasir (assignment permanen ke satu cabang); **null untuk Owner** — bukan berarti Owner "tidak berhubungan dengan cabang manapun", tapi karena Owner punya akses ke semua cabang miliknya sekaligus, tidak terikat satu cabang. Lihat `ARCHITECTURE.md` bagian 2.1 untuk cara Owner memilih cabang saat melakukan aksi yang sifatnya per-cabang. |
| name | string | |
| email | string, unique | |
| password | string | |
| is_active | boolean, default true | Status individual user (terpisah dari status business) |

Role (Superadmin/Owner/Kasir) dikelola via `spatie/laravel-permission` (tabel `roles`, `permissions`, `model_has_roles`, dst — default package, tidak perlu dibuat manual).

## 2. Master Data

### `suppliers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| name | string | |
| phone | string | |
| address | text | |

### `customers` (untuk transaksi berbasis piutang/tempo)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| name | string | |
| phone | string | |
| address | text | |

> Transaksi tanpa piutang (tunai/walk-in) tidak wajib memilih customer — `customer_id` di `sales` bersifat nullable.

### `raw_materials` (Bahan Baku)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| name | string | |
| base_unit | string | Satuan dasar stok, mis. `kg`, `liter` |
| minimum_stock | decimal | Untuk alert stok minimum |

### `raw_material_batches` (untuk FEFO)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| raw_material_id | FK raw_materials | |
| branch_id | FK branches | Stok per cabang, tidak tercampur |
| batch_no | string | |
| quantity_remaining | decimal | Sisa stok batch ini |
| purchase_price | decimal | Untuk kalkulasi HPP |
| expired_date | date, nullable | Dasar pengurutan FEFO |
| received_at | date | |

### `products` (Produk Jadi)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| name | string | |
| sku | string | |
| base_unit | string | Satuan dasar (mis. `pcs`) |
| selling_price | decimal | Harga jual satuan dasar |
| image | string, nullable | via medialibrary |
| halal_cert_number | string, nullable | Nomor sertifikat halal produk ini |
| halal_cert_issuer | string, nullable | Nama lembaga penerbit sertifikasi halal |
| halal_cert_expired_date | date, nullable | Tanggal kedaluwarsa sertifikat halal. Dasar notifikasi "akan expired dalam 30 hari" di dashboard Owner (lihat `PRD.md` bagian 6.11 dan `BUSINESS-RULES.md` bagian 12) |

### `product_units` (Multi-Satuan / Eceran-Borongan)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_id | FK products | |
| unit_name | string | mis. `dus`, `karton` |
| conversion_to_base | decimal | mis. 1 dus = 12 pcs → nilai 12 |
| price_override | decimal, nullable | Harga khusus per satuan ini bila beda dari kalkulasi otomatis |

### `product_batches`
> Menggantikan konsep `product_stocks` versi lama (angka stok tunggal tanpa batch). Sekarang produk jadi memakai batch tracking penuh, setara `raw_material_batches`, karena FEFO diterapkan juga untuk produk jadi (lihat `BUSINESS-RULES.md` bagian 2).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_id | FK products | |
| branch_id | FK branches | |
| production_order_id | FK production_orders, nullable | Null jika batch berasal dari sumber lain (mis. hasil distribusi masuk dari cabang lain, opname penambahan) |
| production_code | string | Denormalisasi dari `production_orders.production_code` untuk tampilan cepat tanpa join, mis. `PRD-20260708-0001` |
| quantity_remaining | decimal | Sisa stok batch ini |
| expired_date | date, nullable | Dasar pengurutan FEFO. Diisi Owner saat production order dibuat (opsional — tidak semua produk punya masa kedaluwarsa) |
| produced_at | timestamp | |

> **Total stok produk** (untuk list/dashboard) dihitung sebagai `SUM(quantity_remaining)` per `product_id` + `branch_id`, sama persis pola `raw_material_batches` — TIDAK ADA lagi tabel agregat stok produk terpisah, konsisten dengan cara bahan baku dihitung.

## 3. Produksi (BOM Multi-Resep)

> **Perubahan skema**: satu produk sekarang bisa punya BANYAK resep berbeda skala (mis. "Resep 100 pcs" dan "Resep 500 pcs" untuk produk yang sama), bukan satu resep tunggal per produk. Saat produksi, Owner memilih SALAH SATU resep — sistem otomatis tahu bahan yang dibutuhkan dan produk yang dihasilkan berdasarkan resep itu, tanpa perlu hitung ulang per-unit.

### `recipes`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_id | FK products | |
| name | string | Nama resep untuk membedakan skala, mis. "Resep 100 pcs", "Resep 500 pcs" |
| yield_quantity | decimal | Jumlah unit produk jadi yang dihasilkan dari SATU KALI proses resep ini |
| is_active | boolean, default true | Resep nonaktif tidak muncul di pilihan saat buat production order baru (histori production order lama tetap utuh) |

### `recipe_items` (menggantikan `product_recipes` versi lama)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| recipe_id | FK recipes | |
| raw_material_id | FK raw_materials | |
| qty_per_batch | decimal | Kebutuhan bahan baku untuk SATU KALI proses resep ini (sesuai `recipes.yield_quantity` resep tersebut) |
| unit | string | Satuan pada resep (bisa beda dari base_unit bahan baku, perlu konversi) |

> Satu produk boleh punya 0, 1, atau banyak `recipes`. Tidak ada lagi konsep "yield" di level `products` — yield sekarang melekat ke tiap resep, bukan ke produk.

### `production_orders`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| branch_id | FK branches | |
| product_id | FK products | Denormalisasi dari `recipe.product_id` untuk kemudahan query/laporan |
| recipe_id | FK recipes | Resep yang dipilih Owner untuk production order ini |
| user_id | FK users | Owner yang menjalankan produksi |
| production_code | string, unique | Kode produksi otomatis, human-readable, mis. `PRD-20260708-0001` (format: `PRD-{YYYYMMDD}-{urutan 4 digit per hari}`). Immutable setelah dibuat, dipakai sebagai label tampilan di `stock_movements` ("Produksi (PRD-20260708-0001)") |
| batch_multiplier | decimal, default 1 | Berapa kali resep ini dijalankan sekaligus (mis. resep 100 pcs dijalankan 3x = 300 pcs). Default 1 = resep dijalankan apa adanya sesuai `recipe.yield_quantity` |
| quantity_target | decimal | **Otomatis dihitung**: `recipe.yield_quantity × batch_multiplier` — bukan input manual bebas, tapi hasil dari pilihan resep + pengali |
| expired_date | date, nullable | Tanggal kedaluwarsa produk hasil produksi ini (opsional — tidak semua produk expired). Diisi Owner saat membuat production order, diturunkan ke `product_batches.expired_date` |
| status | enum(`draft`,`confirmed`,`cancelled`) | |
| produced_at | timestamp | |

### `production_consumptions` (jejak pengurangan aktual per batch, untuk audit FEFO)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| production_order_id | FK production_orders | |
| raw_material_batch_id | FK raw_material_batches | |
| quantity_deducted | decimal | |

## 4. Pembelian & Utang ke Supplier

### `purchases`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| branch_id | FK branches | |
| supplier_id | FK suppliers | |
| user_id | FK users | Owner (pembelian hanya oleh Owner) |
| invoice_no | string | |
| purchase_date | date | |
| total_amount | decimal | |
| payment_status | enum(`unpaid`,`partial`,`paid`) | |

### `purchase_items`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| purchase_id | FK purchases | |
| raw_material_id | FK raw_materials | |
| quantity | decimal | |
| unit_price | decimal | |
| subtotal | decimal | |
| batch_no | string | Untuk generate `raw_material_batches` |
| expired_date | date, nullable | |

### `purchase_payments` (cicilan utang ke supplier)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| purchase_id | FK purchases | |
| amount | decimal | |
| paid_at | date | |
| method | string | |

### `purchase_returns` & `purchase_return_items`
Struktur serupa `purchases`/`purchase_items`, mereferensikan `purchase_id` asal, dipakai untuk retur ke supplier.

## 5. Penjualan & Piutang dari Pembeli

### `cashier_shifts`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| branch_id | FK branches | |
| user_id | FK users | Kasir |
| opening_cash | decimal | |
| closing_cash_system | decimal, nullable | Dihitung sistem dari transaksi |
| closing_cash_actual | decimal, nullable | Input manual kasir saat tutup shift |
| difference | decimal, nullable | |
| opened_at | timestamp | |
| closed_at | timestamp, nullable | |

### `sales`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| branch_id | FK branches | |
| user_id | FK users | Kasir yang melayani |
| customer_id | FK customers, nullable | |
| cashier_shift_id | FK cashier_shifts | |
| invoice_no | string | |
| sale_date | timestamp | |
| subtotal | decimal | |
| discount_type | enum(`nominal`,`percent`), nullable | |
| discount_value | decimal, nullable | |
| tax_percentage_applied | decimal, nullable | **Snapshot**, bukan referensi live ke setting |
| tax_amount | decimal, default 0 | |
| total_amount | decimal | |
| payment_status | enum(`paid`,`partial`,`unpaid`) | Untuk mendukung piutang |

### `sale_items`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| sale_id | FK sales | |
| product_id | FK products | |
| product_unit_id | FK product_units, nullable | Null jika pakai base_unit |
| quantity | decimal | |
| unit_price | decimal | **Snapshot** harga saat transaksi |
| subtotal | decimal | |

### `sale_item_batches` (jejak konsumsi FEFO per item penjualan)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| sale_item_id | FK sale_items | |
| product_batch_id | FK product_batches | |
| quantity_deducted | decimal | Satu `sale_item` bisa mengambil dari LEBIH DARI SATU batch kalau satu batch tidak cukup (sama pola dengan `production_consumptions`) |

### `sale_payments` (cicilan piutang dari pembeli)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| sale_id | FK sales | |
| amount | decimal | |
| paid_at | date | |
| method | string | |

### `sale_returns` & `sale_return_items`
Struktur serupa `sales`/`sale_items`, mereferensikan `sale_id` asal.

## 6. Pengiriman

### `shipments`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| branch_id | FK branches | Cabang asal |
| sale_id | FK sales, nullable | Diisi jika pengiriman terkait barang terjual |
| recipient_name | string | Nama penerima barang — TIDAK selalu sama dengan `customer_id` (mis. dibeli atas nama toko tapi dikirim ke gudang berbeda, atau transaksi tunai/walk-in tanpa `customer_id` sama sekali). Kalau `sale.customer_id` terisi, boleh auto-terisi sebagai default tapi tetap bisa diedit. |
| created_by | FK users | Siapa yang input pengiriman ini — Owner ATAU Kasir (lihat `PERMISSIONS.md`) |
| type | enum(`ecer`,`borongan`) | |
| destination | text | |
| status | enum(`pending`,`shipped`,`delivered`) | |
| shipped_at | timestamp, nullable | |

### `shipment_items`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| shipment_id | FK shipments | |
| product_id | FK products | |
| quantity | decimal | |

### `stock_distributions` (Distribusi Stok Antar Cabang)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| origin_branch_id | FK branches | Cabang asal |
| destination_branch_id | FK branches | Cabang tujuan |
| user_id | FK users | Owner yang menginisiasi (hanya Owner, lintas cabang miliknya sendiri) |
| status | enum(`pending`,`shipped`,`received`) | |
| shipped_at | timestamp, nullable | |
| received_at | timestamp, nullable | |
| notes | text, nullable | |

### `stock_distribution_items`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| stock_distribution_id | FK stock_distributions | |
| item_type | enum(`raw_material`,`product`) | |
| item_id | bigint | |
| quantity | decimal | |

### `stock_distribution_item_batches` (jejak konsumsi FEFO per item distribusi)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| stock_distribution_item_id | FK stock_distribution_items | |
| batch_type | enum(`raw_material`,`product`) | Samakan dengan `item_type` induknya |
| batch_id | bigint | Merujuk ke `raw_material_batches.id` atau `product_batches.id` sesuai `batch_type` |
| quantity | decimal | Qty yang diambil dari batch ini (bisa lebih dari satu batch per item distribusi) |

## 7. Stok & Audit

### `stock_movements` (ledger terpusat, sumber kebenaran pergerakan stok)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| branch_id | FK branches | |
| item_type | enum(`raw_material`,`product`) | |
| item_id | bigint | ID bahan baku atau produk |
| batch_id | bigint, nullable | Merujuk ke `raw_material_batches.id` jika `item_type = raw_material`, atau ke `product_batches.id` jika `item_type = product` (polymorphic berdasarkan `item_type`, bukan foreign key tunggal — tangani di level aplikasi/Eloquent relationship kondisional) |
| movement_type | enum(`in`,`out`) | |
| quantity | decimal | |
| reference_type | string | mis. `purchase`, `production`, `sale`, `stock_opname`, `shipment` |
| reference_id | bigint | |
| created_by | FK users | |

### `stock_opnames`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_id | FK businesses | |
| branch_id | FK branches | |
| item_type | enum(`raw_material`,`product`) | |
| item_id | bigint | |
| batch_id | FK raw_material_batches, nullable | |
| system_quantity | decimal | |
| actual_quantity | decimal | |
| difference | decimal | |
| reason | text | |
| user_id | FK users | |

## 8. Setting per Cabang

### `branch_settings`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| branch_id | FK branches | |
| tax_enabled | boolean, default false | |
| tax_percentage | decimal, nullable | Diisi bila `tax_enabled = true` |

> Alternatif: pakai `spatie/laravel-settings` dengan settings class per branch. Pilih salah satu pendekatan dan konsisten — disarankan tabel eksplisit seperti di atas agar mudah di-join saat perlu laporan lintas cabang.

## 9. Activity Log

Ditangani otomatis oleh `spatie/laravel-activitylog` (tabel `activity_log` bawaan package). Pastikan setiap Model penting menggunakan trait `LogsActivity` sesuai daftar di `AGENTS.md` bagian 7.

## 10. Relasi Kunci (Ringkasan)

```
businesses 1---n branches
branches   1---n users (kasir), raw_material_batches, product_batches, sales, purchases, shipments
products   1---n recipes---1 recipe_items---1 raw_materials
production_orders 1---n recipe_id (resep yang dipilih)
products   1---n product_units
products   1---n product_batches
production_orders 1---n production_consumptions---1 raw_material_batches
production_orders 1---n product_batches (hasil produksi)
purchases  1---n purchase_items, purchase_payments
sales      1---n sale_items, sale_payments
sale_items 1---n sale_item_batches---1 product_batches
sales      1---n shipments (opsional)
branches   1---n stock_distributions (sebagai origin), 1---n stock_distributions (sebagai destination)
stock_distributions 1---n stock_distribution_items
stock_distribution_items 1---n stock_distribution_item_batches
```

## 11. Catatan Implementasi

- Semua pengurangan/penambahan stok (produksi, pembelian, penjualan, opname, retur, distribusi) WAJIB tercatat di `stock_movements` melalui `StockService` terpusat (lihat `AGENTS.md`).
- **FEFO berlaku untuk KEDUA jenis batch**: `raw_material_batches.expired_date ASC` (bahan baku) DAN `product_batches.expired_date ASC` (produk jadi) — diterapkan saat produksi (konsumsi bahan baku), penjualan (konsumsi produk jadi), dan distribusi antar cabang (konsumsi bahan baku maupun produk jadi). Batch tanpa `expired_date` selalu prioritas paling akhir, konsisten di semua konteks ini.
- Konversi satuan antara resep, stok bahan baku, dan `product_units` harus konsisten — pertimbangkan tabel `unit_conversions` global jika kombinasi satuan makin kompleks di fase lanjutan.
- **`production_code`** (di `production_orders`) di-generate otomatis, format `PRD-{YYYYMMDD}-{urutan 4 digit per hari}`, dan didenormalisasi ke `product_batches.production_code` untuk tampilan cepat di `stock_movements` tanpa perlu join berlapis (mis. label "Produksi (PRD-20260708-0001)").
