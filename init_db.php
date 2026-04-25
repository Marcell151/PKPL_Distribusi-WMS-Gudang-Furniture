<?php
require 'config.php';

try {
    // 1. tb_users
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_users (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_lengkap TEXT NOT NULL,
        role TEXT NOT NULL
    )");

    // 2. tb_furniture
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_furniture (
        id_furniture INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_barang TEXT UNIQUE NOT NULL,
        nama_barang TEXT NOT NULL,
        area_blok TEXT,
        stok_tersedia INTEGER DEFAULT 0,
        stok_karantina INTEGER DEFAULT 0
    )");

    // 3. tb_sales_order
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_sales_order (
        id_so INTEGER PRIMARY KEY AUTOINCREMENT,
        no_so TEXT UNIQUE NOT NULL,
        nama_toko_peminta TEXT NOT NULL,
        tanggal_request TEXT NOT NULL,
        status TEXT DEFAULT 'Pending'
    )");

    // 4. tb_detail_so
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_detail_so (
        id_detail INTEGER PRIMARY KEY AUTOINCREMENT,
        id_so INTEGER,
        id_furniture INTEGER,
        qty_diminta INTEGER,
        FOREIGN KEY (id_so) REFERENCES tb_sales_order(id_so),
        FOREIGN KEY (id_furniture) REFERENCES tb_furniture(id_furniture)
    )");

    // 5. tb_mutasi_stok
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_mutasi_stok (
        id_mutasi INTEGER PRIMARY KEY AUTOINCREMENT,
        id_furniture INTEGER,
        tgl_mutasi TEXT NOT NULL,
        jenis_mutasi TEXT NOT NULL,
        qty INTEGER NOT NULL,
        keterangan TEXT,
        FOREIGN KEY (id_furniture) REFERENCES tb_furniture(id_furniture)
    )");

    // 6. tb_nota_selisih
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_nota_selisih (
        id_nota INTEGER PRIMARY KEY AUTOINCREMENT,
        no_po_supplier TEXT NOT NULL,
        id_furniture INTEGER,
        qty_kurang INTEGER NOT NULL,
        keterangan_refund TEXT,
        FOREIGN KEY (id_furniture) REFERENCES tb_furniture(id_furniture)
    )");

    // CLEAR OLD DATA
    $pdo->exec("DELETE FROM tb_users");
    $pdo->exec("DELETE FROM tb_detail_so");
    $pdo->exec("DELETE FROM tb_sales_order");
    $pdo->exec("DELETE FROM tb_mutasi_stok");
    $pdo->exec("DELETE FROM tb_nota_selisih");
    $pdo->exec("DELETE FROM tb_furniture");

    // RESET AUTOINCREMENT
    $pdo->exec("UPDATE sqlite_sequence SET seq = 0");

    // INSERT DUMMY DATA - USERS
    $pdo->exec("INSERT INTO tb_users (id_user, nama_lengkap, role) VALUES 
        (1, 'Andi Wijaya', 'Admin'),
        (2, 'Siti Aminah', 'Supervisor'),
        (3, 'Budi Santoso', 'Staff Gudang'),
        (4, 'Dewi Lestari', 'Staff Gudang')
    ");

    // INSERT DUMMY DATA - FURNITURE (RICH SCENARIO)
    $furniture_data = [
        ['SOFA-001', 'Sofa Minimalis 2 Seater Grey', 'Blok A1', 45, 2],
        ['LMR-001', 'Lemari Pakaian 3 Pintu Putih', 'Blok B2', 28, 0],
        ['MEJA-001', 'Meja Makan Kayu Jati 6 Kursi', 'Blok C1', 15, 1],
        ['KRS-001', 'Kursi Kantor Ergonomis Pro', 'Blok D3', 120, 5],
        ['MJS-001', 'Meja Kerja Minimalis Oak', 'Blok D1', 40, 0],
        ['TDR-001', 'Tempat Tidur King Size Velvet', 'Blok E2', 10, 0],
        ['RAK-001', 'Rak Buku Industrial Steel', 'Blok F1', 35, 3],
        ['MTM-001', 'Meja Tamu Marmer Carrara', 'Blok C2', 8, 2],
        ['LMD-001', 'Lemari Dapur Gantung Modern', 'Blok G1', 20, 0],
        ['KRS-002', 'Kursi Bar Velvet Gold', 'Blok G2', 50, 0]
    ];

    $stmt_furn = $pdo->prepare("INSERT INTO tb_furniture (kode_barang, nama_barang, area_blok, stok_tersedia, stok_karantina) VALUES (?, ?, ?, ?, ?)");
    foreach ($furniture_data as $row) {
        $stmt_furn->execute($row);
    }

    // INSERT DUMMY DATA - SALES ORDERS (MULTIPLE SCENARIOS)
    $so_data = [
        ['SO-20231020-001', 'Toko Furni Jaya Jakarta', date('Y-m-d', strtotime('-5 days')), 'Shipped'],
        ['SO-20231021-002', 'Cabang Furniture Depok', date('Y-m-d', strtotime('-4 days')), 'Pending'],
        ['SO-20231022-003', 'Grand Furniture Bogor', date('Y-m-d', strtotime('-3 days')), 'Pending'],
        ['SO-20231023-004', 'Furniture Minimalis Bekasi', date('Y-m-d', strtotime('-2 days')), 'Shipped'],
        ['SO-20231024-005', 'Toko Interior Tangerang', date('Y-m-d', strtotime('-1 days')), 'Pending']
    ];

    $stmt_so = $pdo->prepare("INSERT INTO tb_sales_order (no_so, nama_toko_peminta, tanggal_request, status) VALUES (?, ?, ?, ?)");
    foreach ($so_data as $row) {
        $stmt_so->execute($row);
    }

    // SO DETAILS
    $pdo->exec("INSERT INTO tb_detail_so (id_so, id_furniture, qty_diminta) VALUES 
        (1, 1, 5), (1, 2, 2),
        (2, 4, 10), (2, 5, 5),
        (3, 6, 2), (3, 7, 5),
        (4, 8, 2), (4, 10, 10),
        (5, 1, 3), (5, 9, 1)
    ");

    // MUTATION HISTORY (RICH LOGS)
    $mutasi_data = [
        [1, date('Y-m-d H:i:s', strtotime('-15 days')), 'IN', 52, 'Initial Stock PO-001'],
        [1, date('Y-m-d H:i:s', strtotime('-10 days')), 'MUTASI_RUSAK', -2, 'Cacat permukaan kain'],
        [1, date('Y-m-d H:i:s', strtotime('-5 days')), 'OUT', -5, 'Kirim SO-001'],
        [2, date('Y-m-d H:i:s', strtotime('-15 days')), 'IN', 30, 'Initial Stock PO-002'],
        [2, date('Y-m-d H:i:s', strtotime('-5 days')), 'OUT', -2, 'Kirim SO-001'],
        [3, date('Y-m-d H:i:s', strtotime('-15 days')), 'IN', 20, 'Initial Stock PO-003'],
        [3, date('Y-m-d H:i:s', strtotime('-12 days')), 'MUTASI_RUSAK', -1, 'Kaki meja retak'],
        [3, date('Y-m-d H:i:s', strtotime('-8 days')), 'ADJUST_OPNAME', -4, 'Selisih opname stok hilang'],
        [4, date('Y-m-d H:i:s', strtotime('-20 days')), 'IN', 150, 'Import PO-004'],
        [4, date('Y-m-d H:i:s', strtotime('-18 days')), 'MUTASI_RUSAK', -5, 'Hidrolik rusak'],
        [4, date('Y-m-d H:i:s', strtotime('-15 days')), 'OUT', -25, 'Kirim Toko Lama'],
        [7, date('Y-m-d H:i:s', strtotime('-10 days')), 'IN', 40, 'PO-005'],
        [7, date('Y-m-d H:i:s', strtotime('-5 days')), 'MUTASI_RUSAK', -3, 'Gagal QC Pre-Delivery'],
        [7, date('Y-m-d H:i:s', strtotime('-2 days')), 'ADJUST_OPNAME', -2, 'Adjustment stock gudang'],
        [8, date('Y-m-d H:i:s', strtotime('-10 days')), 'IN', 12, 'PO-006'],
        [8, date('Y-m-d H:i:s', strtotime('-2 days')), 'OUT', -2, 'Kirim SO-004'],
        [8, date('Y-m-d H:i:s', strtotime('-1 days')), 'MUTASI_RUSAK', -2, 'Pecah saat handling'],
        [10, date('Y-m-d H:i:s', strtotime('-5 days')), 'IN', 60, 'PO-007'],
        [10, date('Y-m-d H:i:s', strtotime('-2 days')), 'OUT', -10, 'Kirim SO-004']
    ];

    $stmt_mut = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan) VALUES (?, ?, ?, ?, ?)");
    foreach ($mutasi_data as $row) {
        $stmt_mut->execute($row);
    }

    // NOTA SELISIH (REFUNDS)
    $pdo->exec("INSERT INTO tb_nota_selisih (no_po_supplier, id_furniture, qty_kurang, keterangan_refund) VALUES 
        ('PO-2023-005', 7, 5, 'Barang kurang 5 unit dari supplier saat bongkar muat di Surabaya.'),
        ('PO-2023-008', 3, 2, '2 unit Meja Makan pecah saat pengiriman supplier.')
    ");

    echo "<div style='font-family: sans-serif; padding: 40px; text-align: center; background: #f8fafc; min-height: 100vh;'>
            <div style='background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); display: inline-block;'>
                <h1 style='color: #1e3a8a; margin-bottom: 10px;'>WMS-Furni DB Initialized!</h1>
                <p style='color: #64748b;'>Database berhasil diisi dengan skenario lengkap (10 Barang, 5 SO, & Riwayat Mutasi).</p>
                <a href='dashboard.php' style='display: inline-block; margin-top: 20px; background: #1e3a8a; color: white; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: bold;'>Buka Dashboard</a>
            </div>
          </div>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
