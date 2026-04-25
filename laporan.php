<?php
require 'config.php';
require_access(['Admin', 'Supervisor']);

$bulan = date('Y-m');
$query = "SELECT f.kode_barang, f.nama_barang, f.stok_tersedia as sisa, f.stok_karantina, COALESCE(SUM(CASE WHEN m.jenis_mutasi = 'IN' AND strftime('%Y-%m', m.tgl_mutasi) = '$bulan' THEN m.qty ELSE 0 END), 0) as inb, COALESCE(SUM(CASE WHEN m.jenis_mutasi = 'OUT' AND strftime('%Y-%m', m.tgl_mutasi) = '$bulan' THEN ABS(m.qty) ELSE 0 END), 0) as outb, COALESCE(SUM(CASE WHEN m.jenis_mutasi = 'MUTASI_RUSAK' AND strftime('%Y-%m', m.tgl_mutasi) = '$bulan' THEN ABS(m.qty) ELSE 0 END), 0) as rsk FROM tb_furniture f LEFT JOIN tb_mutasi_stok m ON f.id_furniture = m.id_furniture GROUP BY f.id_furniture ORDER BY f.nama_barang ASC";
$stmt = $pdo->query($query); $laporan = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in print:p-0">
    <header class="flex justify-between items-center mb-10 print:hidden">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Konsolidasi Laporan</h2>
            <p class="text-slate-500 font-medium mt-1">Audit logistik periode <?= date('F Y') ?>.</p>
        </div>
        <button onclick="window.print()" class="bg-navy-900 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-navy-900/10 hover:bg-navy-800 transition-all">Cetak Laporan</button>
    </header>

    <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden print:border-none print:shadow-none">
        <div class="p-12 border-b border-slate-50 text-center bg-slate-50 print:bg-white">
            <h1 class="text-3xl font-black text-navy-900 uppercase tracking-tighter mb-2">WMS-Furni Corporate</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">Monthly Logistics Summary | <?= date('M Y') ?></p>
        </div>
        <div class="p-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b-2 border-slate-100">
                        <th class="px-6 py-6">Produk</th>
                        <th class="px-6 py-6 text-center">Inbound</th>
                        <th class="px-6 py-6 text-center">Outbound</th>
                        <th class="px-6 py-6 text-center">Rusak</th>
                        <th class="px-6 py-6 text-center">Tersedia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($laporan as $r): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-6">
                            <p class="font-black text-navy-900"><?= $r['kode_barang'] ?></p>
                            <p class="text-[10px] text-slate-500"><?= $r['nama_barang'] ?></p>
                        </td>
                        <td class="px-6 py-6 text-center font-bold text-blue-600"><?= $r['inb'] ?></td>
                        <td class="px-6 py-6 text-center font-bold text-green-600"><?= $r['outb'] ?></td>
                        <td class="px-6 py-6 text-center font-bold text-red-500"><?= $r['rsk'] ?></td>
                        <td class="px-6 py-6 text-center font-black text-lg text-navy-900 bg-slate-50/50"><?= $r['sisa'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="mt-20 hidden print:flex justify-around text-center">
                <div>
                    <div class="w-40 border-b border-navy-900 mb-2 mx-auto"></div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Warehouse Manager</p>
                </div>
                <div>
                    <div class="w-40 border-b border-navy-900 mb-2 mx-auto"></div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Inventory Auditor</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        @page { size: portrait; margin: 1.5cm; }
        aside, .print\:hidden { display: none !important; }
        main { height: auto !important; overflow: visible !important; }
        .animate-fade-in { transform: none !important; animation: none !important; }
    }
</style>

<?php include 'includes/footer.php'; ?>
