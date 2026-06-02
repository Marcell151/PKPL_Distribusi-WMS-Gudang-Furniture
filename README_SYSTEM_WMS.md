# WMS-Furni: System Architecture & Workflow Documentation

Dokumen ini berisi rangkuman teknis dan operasional (SOP) dari sistem **WMS-Furni (Warehouse Management System - Edisi Distributor Furniture)**. Dokumen ini dapat digunakan sebagai rujukan (*context*) bagi AI (Gemini) atau *developer* untuk memahami cara kerja sistem secara keseluruhan setelah penyesuaian hak akses dan alur *Waste Management*.

---

## 1. Tech Stack & Environment
- **Bahasa Pemrograman:** PHP (Native/Procedural) dengan PDO.
- **Database:** SQLite (`wms_furni.sqlite`).
- **Styling UI:** Tailwind CSS (via CDN) dengan tema *Corporate Navy* & *Amber*.
- **Iconography:** Heroicons (SVG).
- **Session Management:** PHP Native `$_SESSION` dengan fitur *switch user* untuk kemudahan *testing*.

---

## 2. Struktur Database (Schema Overview)
Sistem memiliki beberapa tabel utama yang saling terelasi:

1. **Master Data**
   - `tb_users`: Menyimpan data pengguna dan *role* (Admin, Supervisor, Staff Gudang).
   - `tb_furniture`: Master data barang (Stok Aktif, Stok Karantina, Harga, Relasi ke Lokasi).
   - `tb_lokasi`: Master denah gudang (Blok dan Rak).
   - `tb_supplier`: Vendor asal barang (untuk *Inbound* / PO).
   - `tb_toko`: Cabang atau *customer* tujuan pengiriman (untuk *Outbound* / SO).

2. **Transaksional**
   - `tb_purchase_order` & `tb_detail_po`: Mencatat pesanan pembelian ke *Supplier*.
   - `tb_sales_order` & `tb_detail_so`: Mencatat permintaan pengiriman ke *Toko Cabang*.
   - `tb_mutasi_stok`: **(Tabel Krusial)** Buku besar (buku log) yang mencatat *setiap* perubahan stok. Semua aksi (Inbound, Outbound, Rusak, Opname) *wajib* masuk ke tabel ini untuk pelacakan.
   
3. **Operasional Khusus**
   - `tb_opname`: Mencatat riwayat pengajuan penyesuaian stok (audit fisik) beserta status persetujuannya.
   - `tb_waste_insidentil`: Mencatat pelaporan barang rusak yang ditemukan secara tiba-tiba di gudang beserta kronologi dan status persetujuannya.

---

## 3. Role Matrix & SOP (Standard Operating Procedure)

Sistem diatur menggunakan fungsi *wrapper* `require_access(['Role'])` untuk mencegah akses yang tidak sah ke dalam halaman.

| Fitur / Halaman | Admin | Supervisor | Staff Gudang | Deskripsi SOP |
| :--- | :---: | :---: | :---: | :--- |
| **Master Data** | ✅ | ❌ | ❌ | Hanya Admin (atau IT/Direktur) yang berhak mengubah data inti. |
| **Transaksi PO & SO** | ✅ | ✅ | ❌ | Supervisor bertugas membuat dan menyetujui dokumen komersial. |
| **Inbound & Outbound** | ✅ | ❌ | ✅ | *Eksekusi Fisik*: Hanya Staff Gudang yang melakukan klik penerimaan dan pengiriman. |
| **Lapor Waste (Rusak)** | ✅ | ❌ | ✅ | Jika ada barang rusak insidentil, Staff membuat laporan (tidak langsung potong stok). |
| **Approval Waste** | ✅ | ✅ | ❌ | Supervisor wajib memverifikasi dan menyetujui pemindahan stok aktif ke stok karantina. |
| **Request Opname** | ✅ | ❌ | ✅ | Staff mengaudit fisik dan mengajukan selisih ke sistem. |
| **Approval Opname** | ✅ | ✅ | ❌ | Supervisor menelaah alasan selisih stok, lalu menyetujui agar stok disesuaikan. |
| **Mutasi Internal** | ✅ | ✅ | ✅ | Fitur memindah rak/blok barang di dalam gudang. |
| **Laporan & Riwayat** | ✅ | ✅ | ❌ | Supervisor dan Admin dapat memonitor *log* dan mencetak rekapitulasi. |

---

## 4. Alur Kerja Utama (Core Workflows)

### A. Alur Barang Masuk (Inbound / Replenishment)
1. **Supervisor** membuat *Purchase Order* (PO) melalui menu **Purchase Order (PO)**.
2. Saat barang fisik tiba dari truk, **Staff Gudang** membuka menu **Inbound (Terima)**.
3. Staff memasukkan nomor PO, mencentang barang yang diterima, dan mengeklik *Terima Barang*.
4. **Sistem** menambah `stok_tersedia` di `tb_furniture` dan mencatat jenis mutasi `IN` di `tb_mutasi_stok`.

### B. Alur Barang Keluar (Outbound / Fulfillment)
1. Permintaan pesanan masuk menjadi *Sales Order* (SO). **Supervisor** meninjau SO tersebut.
2. **Staff Gudang** membuka menu **Outbound & QC**.
3. Staff melakukan proses tahap demi tahap: *Picking* (Ambil barang) $\rightarrow$ *Packing* $\rightarrow$ *QC Passed* $\rightarrow$ *Shipped* (Kirim).
4. Saat status menjadi *Shipped*, **Sistem** mengurangi `stok_tersedia` dan mencatat mutasi `OUT`.

### C. Alur Manajemen Kerusakan (Waste Management)
> Fitur ini menangani kerusakan yang ditemukan secara tidak sengaja di dalam gudang (contoh: barang tersenggol forklift), BUKAN saat penerimaan barang.

1. **Staff Gudang** menemukan barang rusak.
2. Staff membuka menu **Lapor Kerusakan & Waste** (`lapor_waste.php`), memilih barang, memasukkan Qty Rusak, dan menulis kronologi. Status menjadi *Menunggu Approval*.
3. **Supervisor** melihat notifikasi (badge merah) di menu **Approval Waste** (`approval_waste.php`).
4. Supervisor mengeklik **Approve**.
5. **Sistem** melakukan transaksi database berantai (*transaction*):
   - Mengurangi `stok_tersedia`.
   - Menambah `stok_karantina`.
   - Mencatat ke `tb_mutasi_stok` (jenis `MUTASI_RUSAK` bernilai minus).
   - Mengubah status laporan menjadi *Approved* dan mencatat `id_user_approve`.

### D. Alur Stock Opname (Audit Fisik)
1. **Staff Gudang** menghitung fisik barang. Jika ada perbedaan dengan sistem (misal sistem: 10, fisik: 8), Staff membuka menu **Kartu Stok & Opname**.
2. Staff mengeklik **Request Opname**, memasukkan angka fisik aktual (8) dan alasannya.
3. **Supervisor** menerima *request* tersebut di tab **Approval Opname**.
4. Supervisor mengeklik **Setujui**.
5. **Sistem** menimpa stok lama menjadi stok aktual (10 $\rightarrow$ 8), mencatat selisih (-2) ke `tb_mutasi_stok` dengan jenis `ADJUST_OPNAME`.

---

## 5. Security & Error Handling
- **Database Transactions:** Semua operasi yang memengaruhi lebih dari satu tabel (contoh: Approval Waste mengubah `tb_furniture`, `tb_mutasi_stok`, dan `tb_waste_insidentil`) dibungkus dalam blok `try { $pdo->beginTransaction(); ... $pdo->commit(); } catch { $pdo->rollBack(); }` untuk menghindari inkonsistensi data jika server terputus di tengah jalan.
- **Role Enforcement:** Akses peran tidak hanya disembunyikan dari UI, tetapi di-blokir pada tingkat *server* menggunakan `require_access()` di baris paling atas (sebelum logika `$_POST` dijalankan).

---

*Dokumen ini dibuat otomatis pada sesi pengembangan WMS-Furni untuk membantu AI (LLM) memahami lanskap kode dengan cepat.*
