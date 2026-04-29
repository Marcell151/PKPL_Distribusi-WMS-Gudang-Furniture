<?php
require 'config.php';

// Fetch all Outbound Transactions (Sales Orders)
$stmt_so = $pdo->query("
    SELECT s.no_so as no_transaksi, s.tanggal_request as tanggal, 'OUTBOUND (Penjualan)' as jenis,
           t.nama_toko as entitas, f.nama_barang, d.qty_diminta as qty, s.status, u.nama_lengkap as pemroses
    FROM tb_sales_order s
    JOIN tb_detail_so d ON s.id_so = d.id_so
    JOIN tb_furniture f ON d.id_furniture = f.id_furniture
    LEFT JOIN tb_toko t ON s.id_toko = t.id_toko
    LEFT JOIN tb_users u ON s.id_user = u.id_user
");
$outbound_trans = $stmt_so->fetchAll();

// Fetch all Inbound Transactions (Purchase Orders)
$stmt_po = $pdo->query("
    SELECT p.no_po as no_transaksi, p.tanggal_po as tanggal, 'INBOUND (Pembelian)' as jenis,
           sup.nama_supplier as entitas, f.nama_barang, d.qty_dipesan as qty, p.status, u.nama_lengkap as pemroses
    FROM tb_purchase_order p
    JOIN tb_detail_po d ON p.id_po = d.id_po
    JOIN tb_furniture f ON d.id_furniture = f.id_furniture
    LEFT JOIN tb_supplier sup ON p.id_supplier = sup.id_supplier
    LEFT JOIN tb_users u ON p.id_user = u.id_user
");
$inbound_trans = $stmt_po->fetchAll();

$semua_transaksi = array_merge($outbound_trans, $inbound_trans);
usort($semua_transaksi, function($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="mb-10">
        <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Riwayat Transaksi</h2>
        <p class="text-slate-500 font-medium mt-1">Lacak seluruh aktivitas permintaan barang (Masuk & Keluar) beserta PIC-nya.</p>
    </header>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">No Transaksi</th>
                        <th class="px-8 py-6">Tanggal</th>
                        <th class="px-8 py-6">Jenis</th>
                        <th class="px-8 py-6">Entitas (Toko/Supplier)</th>
                        <th class="px-8 py-6">Barang & Qty</th>
                        <th class="px-8 py-6 text-center">PIC / Diorder Oleh</th>
                        <th class="px-8 py-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($semua_transaksi as $t): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6"><span class="font-black text-navy-900"><?= htmlspecialchars($t['no_transaksi']) ?></span></td>
                        <td class="px-8 py-6 font-bold text-slate-600"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                        <td class="px-8 py-6">
                            <?php if(strpos($t['jenis'], 'INBOUND') !== false): ?>
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">Inbound</span>
                            <?php else: ?>
                                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">Outbound</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-6 font-bold text-navy-900"><?= htmlspecialchars($t['entitas'] ?? 'N/A') ?></td>
                        <td class="px-8 py-6">
                            <p class="text-xs font-bold text-slate-700 truncate max-w-[200px]"><?= htmlspecialchars($t['nama_barang']) ?></p>
                            <p class="text-[10px] font-black text-slate-400"><?= $t['qty'] ?> Unit</p>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <div class="inline-flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                                <div class="w-5 h-5 rounded-full bg-navy-900 text-white flex items-center justify-center text-[8px] font-bold">
                                    <?= substr($t['pemroses'] ?? 'SYS', 0, 1) ?>
                                </div>
                                <span class="text-xs font-bold text-slate-600"><?= htmlspecialchars($t['pemroses'] ?? 'Sistem') ?></span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest"><?= str_replace('_', ' ', $t['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
