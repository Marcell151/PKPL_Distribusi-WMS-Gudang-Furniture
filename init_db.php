<?php
require 'config.php';

try {
    // 1. tb_users
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_users (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_lengkap TEXT NOT NULL,
        role TEXT NOT NULL
    )");

    // 2. tb_supplier
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_supplier (
        id_supplier INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_supplier TEXT UNIQUE NOT NULL,
        nama_supplier TEXT NOT NULL,
        kontak TEXT,
        alamat TEXT
    )");

    // 3. tb_toko
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_toko (
        id_toko INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_toko TEXT UNIQUE NOT NULL,
        nama_toko TEXT NOT NULL,
        kontak TEXT,
        alamat TEXT
    )");

    // 4. tb_lokasi
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_lokasi (
        id_lokasi INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_blok TEXT NOT NULL,
        rak TEXT NOT NULL,
        deskripsi TEXT
    )");

    // 5. tb_furniture
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_furniture (
        id_furniture INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_barang TEXT UNIQUE NOT NULL,
        nama_barang TEXT NOT NULL,
        id_lokasi INTEGER,
        stok_tersedia INTEGER DEFAULT 0,
        stok_karantina INTEGER DEFAULT 0,
        FOREIGN KEY (id_lokasi) REFERENCES tb_lokasi(id_lokasi)
    )");

    // 6. tb_sales_order
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_sales_order (
        id_so INTEGER PRIMARY KEY AUTOINCREMENT,
        no_so TEXT UNIQUE NOT NULL,
        id_toko INTEGER,
        tanggal_request TEXT NOT NULL,
        status TEXT DEFAULT 'Pending',
        FOREIGN KEY (id_toko) REFERENCES tb_toko(id_toko)
    )");

    // 7. tb_detail_so
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_detail_so (
        id_detail INTEGER PRIMARY KEY AUTOINCREMENT,
        id_so INTEGER,
        id_furniture INTEGER,
        qty_diminta INTEGER,
        FOREIGN KEY (id_so) REFERENCES tb_sales_order(id_so),
        FOREIGN KEY (id_furniture) REFERENCES tb_furniture(id_furniture)
    )");

    // 8. tb_mutasi_stok
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_mutasi_stok (
        id_mutasi INTEGER PRIMARY KEY AUTOINCREMENT,
        id_furniture INTEGER,
        tgl_mutasi TEXT NOT NULL,
        jenis_mutasi TEXT NOT NULL,
        qty INTEGER NOT NULL,
        keterangan TEXT,
        FOREIGN KEY (id_furniture) REFERENCES tb_furniture(id_furniture)
    )");

    // 9. tb_nota_selisih
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_nota_selisih (
        id_nota INTEGER PRIMARY KEY AUTOINCREMENT,
        no_po_supplier TEXT NOT NULL,
        id_furniture INTEGER,
        qty_kurang INTEGER NOT NULL,
        keterangan_refund TEXT,
        FOREIGN KEY (id_furniture) REFERENCES tb_furniture(id_furniture)
    )");

    // 10. tb_opname
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_opname (
        id_opname INTEGER PRIMARY KEY AUTOINCREMENT,
        tgl_request TEXT NOT NULL,
        id_furniture INTEGER,
        qty_sistem INTEGER,
        qty_fisik INTEGER,
        alasan TEXT,
        status TEXT DEFAULT 'Pending Approval',
        id_user_request INTEGER,
        FOREIGN KEY (id_furniture) REFERENCES tb_furniture(id_furniture)
    )");


    // CLEAR OLD DATA
    $tables = ['tb_users', 'tb_detail_so', 'tb_sales_order', 'tb_mutasi_stok', 'tb_nota_selisih', 'tb_opname', 'tb_furniture', 'tb_lokasi', 'tb_toko', 'tb_supplier'];
    foreach($tables as $t) { $pdo->exec("DELETE FROM $t"); }
    $pdo->exec("UPDATE sqlite_sequence SET seq = 0");

    // DUMMY DATA USERS
    $pdo->exec("INSERT INTO tb_users (id_user, nama_lengkap, role) VALUES 
        (1, 'Andi Wijaya', 'Admin'),
        (2, 'Siti Aminah', 'Supervisor'),
        (3, 'Budi Santoso', 'Staff Gudang')
    ");

    // DUMMY DATA LOKASI
    $lokasi_data = [
        ['Blok A', 'Rak 01', 'Barang Fast Moving'],
        ['Blok B', 'Rak 01', 'Furniture Kayu'],
        ['Blok C', 'Rak 02', 'Sofa & Busa'],
        ['Blok D', 'Rak 01', 'Aksesoris'],
        ['Karantina', 'Rak K-01', 'Area Barang Rusak']
    ];
    $stmt_lok = $pdo->prepare("INSERT INTO tb_lokasi (nama_blok, rak, deskripsi) VALUES (?, ?, ?)");
    foreach ($lokasi_data as $row) { $stmt_lok->execute($row); }

    // DUMMY DATA SUPPLIER
    $sup_data = [
        ['SUP-001', 'PT. Kayu Jati Jepara', '08123456789', 'Jepara, Jawa Tengah'],
        ['SUP-002', 'CV. Busa Empuk Makmur', '08198765432', 'Bandung, Jawa Barat']
    ];
    $stmt_sup = $pdo->prepare("INSERT INTO tb_supplier (kode_supplier, nama_supplier, kontak, alamat) VALUES (?, ?, ?, ?)");
    foreach ($sup_data as $row) { $stmt_sup->execute($row); }

    // DUMMY DATA TOKO
    $toko_data = [
        ['TK-001', 'Toko Furni Jaya Jakarta', '021-111111', 'Jakarta Selatan'],
        ['TK-002', 'Cabang Furniture Depok', '021-222222', 'Depok'],
        ['TK-003', 'Grand Furniture Bogor', '0251-333333', 'Bogor']
    ];
    $stmt_toko = $pdo->prepare("INSERT INTO tb_toko (kode_toko, nama_toko, kontak, alamat) VALUES (?, ?, ?, ?)");
    foreach ($toko_data as $row) { $stmt_toko->execute($row); }

    // DUMMY DATA FURNITURE
    $furniture_data = [
        ['SOFA-001', 'Sofa Minimalis 2 Seater Grey', 3, 45, 2],
        ['LMR-001', 'Lemari Pakaian 3 Pintu Putih', 2, 28, 0],
        ['MEJA-001', 'Meja Makan Kayu Jati 6 Kursi', 2, 15, 1],
        ['KRS-001', 'Kursi Kantor Ergonomis Pro', 1, 120, 5],
        ['MJS-001', 'Meja Kerja Minimalis Oak', 1, 40, 0]
    ];
    $stmt_furn = $pdo->prepare("INSERT INTO tb_furniture (kode_barang, nama_barang, id_lokasi, stok_tersedia, stok_karantina) VALUES (?, ?, ?, ?, ?)");
    foreach ($furniture_data as $row) { $stmt_furn->execute($row); }

    // DUMMY SALES ORDER (Status: Pending, Picking, QC, Packing, Shipped)
    $so_data = [
        ['SO-20231020-001', 1, date('Y-m-d', strtotime('-5 days')), 'Shipped'],
        ['SO-20231021-002', 2, date('Y-m-d', strtotime('-4 days')), 'Pending'],
        ['SO-20231022-003', 3, date('Y-m-d', strtotime('-3 days')), 'Picking'],
        ['SO-20231023-004', 1, date('Y-m-d', strtotime('-2 days')), 'QC_Passed'],
        ['SO-20231024-005', 2, date('Y-m-d', strtotime('-1 days')), 'Packing']
    ];
    $stmt_so = $pdo->prepare("INSERT INTO tb_sales_order (no_so, id_toko, tanggal_request, status) VALUES (?, ?, ?, ?)");
    foreach ($so_data as $row) { $stmt_so->execute($row); }

    // SO DETAILS
    $pdo->exec("INSERT INTO tb_detail_so (id_so, id_furniture, qty_diminta) VALUES 
        (1, 1, 5), (1, 2, 2),
        (2, 4, 10), (2, 5, 5),
        (3, 1, 2), (3, 3, 5),
        (4, 2, 2), (4, 4, 10),
        (5, 1, 3), (5, 5, 1)
    ");

    // MUTATION HISTORY
    $mutasi_data = [
        [1, date('Y-m-d H:i:s', strtotime('-15 days')), 'IN', 52, 'Penerimaan PO-001 SUP-002'],
        [1, date('Y-m-d H:i:s', strtotime('-10 days')), 'MUTASI_RUSAK', -2, 'Cacat permukaan kain'],
        [1, date('Y-m-d H:i:s', strtotime('-5 days')), 'OUT', -5, 'Kirim SO-001']
    ];
    $stmt_mut = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan) VALUES (?, ?, ?, ?, ?)");
    foreach ($mutasi_data as $row) { $stmt_mut->execute($row); }

    echo "<div style='font-family: sans-serif; padding: 40px; text-align: center; background: #f8fafc; min-height: 100vh;'>
            <div style='background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); display: inline-block;'>
                <h1 style='color: #1e3a8a; margin-bottom: 10px;'>WMS Enterprise DB Initialized!</h1>
                <p style='color: #64748b;'>Database berhasil direset & diisi dengan skenario lengkap Enterprise WMS.</p>
                <a href='dashboard.php' style='display: inline-block; margin-top: 20px; background: #1e3a8a; color: white; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: bold;'>Buka Dashboard</a>
            </div>
          </div>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
