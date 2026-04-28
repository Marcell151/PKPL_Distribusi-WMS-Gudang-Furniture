<?php
require 'config.php';
require_access(['Admin', 'Supervisor']);

$bulan = date('Y-m');
$query = "SELECT f.kode_barang, f.nama_barang, f.stok_tersedia as sisa, f.stok_karantina, COALESCE(SUM(CASE WHEN m.jenis_mutasi = 'IN' AND strftime('%Y-%m', m.tgl_mutasi) = '$bulan' THEN m.qty ELSE 0 END), 0) as inb, COALESCE(SUM(CASE WHEN m.jenis_mutasi = 'OUT' AND strftime('%Y-%m', m.tgl_mutasi) = '$bulan' THEN ABS(m.qty) ELSE 0 END), 0) as outb, COALESCE(SUM(CASE WHEN m.jenis_mutasi = 'MUTASI_RUSAK' AND strftime('%Y-%m', m.tgl_mutasi) = '$bulan' THEN ABS(m.qty) ELSE 0 END), 0) as rsk FROM tb_furniture f LEFT JOIN tb_mutasi_stok m ON f.id_furniture = m.id_furniture GROUP BY f.id_furniture ORDER BY f.nama_barang ASC";
$stmt = $pdo->query($query); $laporan = $stmt->fetchAll();

$stmt = $pdo->query("SELECT s.no_so, s.tanggal_request, t.nama_toko, f.nama_barang, d.qty_diminta FROM tb_sales_order s JOIN tb_detail_so d ON s.id_so = d.id_so JOIN tb_furniture f ON d.id_furniture = f.id_furniture JOIN tb_toko t ON s.id_toko = t.id_toko WHERE s.status = 'Shipped' AND strftime('%Y-%m', s.tanggal_request) = '$bulan' ORDER BY s.tanggal_request DESC");
$laporan_so = $stmt->fetchAll();

$stmt = $pdo->query("SELECT o.tgl_request, f.nama_barang, o.qty_sistem, o.qty_fisik, o.alasan, u.nama_lengkap FROM tb_opname o JOIN tb_furniture f ON o.id_furniture = f.id_furniture JOIN tb_users u ON o.id_user_approve = u.id_user WHERE o.status = 'Approved' AND strftime('%Y-%m', o.tgl_request) = '$bulan' ORDER BY o.tgl_request DESC");
$laporan_opname = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in print:p-0">
    <header class="flex justify-between items-center mb-6 print:hidden">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Laporan Terpadu</h2>
            <p class="text-slate-500 font-medium mt-1">Audit logistik periode <?= date('F Y') ?>.</p>
        </div>
        <button onclick="window.print()" class="bg-navy-900 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-navy-900/10 hover:bg-navy-800 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Laporan
        </button>
    </header>

    <div class="flex gap-2 mb-10 bg-slate-200 p-2 rounded-2xl w-fit print:hidden">
        <button id="btn-stok" onclick="stl('stok')" class="px-6 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg">1. Mutasi Stok</button>
        <button id="btn-so" onclick="stl('so')" class="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50">2. Pengiriman (SO)</button>
        <button id="btn-opname" onclick="stl('opname')" class="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50">3. Audit Opname</button>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden print:border-none print:shadow-none">
        <div class="p-12 border-b border-slate-50 text-center bg-slate-50 print:bg-white print:border-navy-900 print:border-b-2">
            <h1 class="text-3xl font-black text-navy-900 uppercase tracking-tighter mb-2">WMS-Furni Enterprise</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]" id="print-title">Monthly Logistics Summary | <?= date('M Y') ?></p>
        </div>
        <div class="p-8">
            <!-- TAB 1: MUTASI STOK -->
            <div id="tab-stok" class="animate-fade-in">
                <table class="w-full text-left print:border-collapse">
                    <thead>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b-2 border-slate-100 print:border-slate-800 print:text-black">
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Produk</th>
                            <th class="px-6 py-6 text-center print:py-3 print:px-2 print:border">Inbound</th>
                            <th class="px-6 py-6 text-center print:py-3 print:px-2 print:border">Outbound</th>
                            <th class="px-6 py-6 text-center print:py-3 print:px-2 print:border">Rusak</th>
                            <th class="px-6 py-6 text-center print:py-3 print:px-2 print:border">Tersedia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        <?php foreach($laporan as $r): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors print:divide-y-0 print:border-b print:border-slate-200">
                            <td class="px-6 py-6 print:py-3 print:px-2 print:border">
                                <p class="font-black text-navy-900 print:text-black"><?= $r['kode_barang'] ?></p>
                                <p class="text-[10px] text-slate-500 print:text-black"><?= $r['nama_barang'] ?></p>
                            </td>
                            <td class="px-6 py-6 text-center font-bold text-blue-600 print:text-black print:py-3 print:px-2 print:border"><?= $r['inb'] ?></td>
                            <td class="px-6 py-6 text-center font-bold text-green-600 print:text-black print:py-3 print:px-2 print:border"><?= $r['outb'] ?></td>
                            <td class="px-6 py-6 text-center font-bold text-red-500 print:text-black print:py-3 print:px-2 print:border"><?= $r['rsk'] ?></td>
                            <td class="px-6 py-6 text-center font-black text-lg text-navy-900 bg-slate-50/50 print:bg-transparent print:text-black print:py-3 print:px-2 print:border"><?= $r['sisa'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 2: PENGIRIMAN SO -->
            <div id="tab-so" class="hidden animate-fade-in">
                <table class="w-full text-left print:border-collapse">
                    <thead>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b-2 border-slate-100 print:border-slate-800 print:text-black">
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Tgl Request</th>
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">No. SO</th>
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Toko Tujuan</th>
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Produk</th>
                            <th class="px-6 py-6 text-center print:py-3 print:px-2 print:border">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        <?php foreach($laporan_so as $s): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors print:divide-y-0 print:border-b print:border-slate-200">
                            <td class="px-6 py-6 text-slate-500 font-medium print:py-3 print:px-2 print:border print:text-black"><?= date('d M Y', strtotime($s['tanggal_request'])) ?></td>
                            <td class="px-6 py-6 font-black text-navy-900 print:py-3 print:px-2 print:border print:text-black"><?= htmlspecialchars($s['no_so']) ?></td>
                            <td class="px-6 py-6 font-bold text-slate-700 print:py-3 print:px-2 print:border print:text-black"><?= htmlspecialchars($s['nama_toko']) ?></td>
                            <td class="px-6 py-6 font-bold text-slate-600 print:py-3 print:px-2 print:border print:text-black"><?= htmlspecialchars($s['nama_barang']) ?></td>
                            <td class="px-6 py-6 text-center font-black text-lg text-green-600 print:py-3 print:px-2 print:border print:text-black"><?= $s['qty_diminta'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($laporan_so)): ?><tr><td colspan="5" class="text-center py-10 text-slate-400 font-bold">Tidak ada data SO dikirim bulan ini.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 3: AUDIT OPNAME -->
            <div id="tab-opname" class="hidden animate-fade-in">
                <table class="w-full text-left print:border-collapse">
                    <thead>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b-2 border-slate-100 print:border-slate-800 print:text-black">
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Tgl Approve</th>
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Produk</th>
                            <th class="px-6 py-6 text-center print:py-3 print:px-2 print:border">Selisih</th>
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Alasan</th>
                            <th class="px-6 py-6 print:py-3 print:px-2 print:border">Disetujui Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        <?php foreach($laporan_opname as $o): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors print:divide-y-0 print:border-b print:border-slate-200">
                            <td class="px-6 py-6 text-slate-500 font-medium print:py-3 print:px-2 print:border print:text-black"><?= date('d M Y H:i', strtotime($o['tgl_request'])) ?></td>
                            <td class="px-6 py-6 font-black text-navy-900 print:py-3 print:px-2 print:border print:text-black"><?= htmlspecialchars($o['nama_barang']) ?></td>
                            <td class="px-6 py-6 text-center font-black text-lg text-amber-600 print:py-3 print:px-2 print:border print:text-black"><?= $o['qty_fisik'] - $o['qty_sistem'] ?></td>
                            <td class="px-6 py-6 text-slate-500 italic text-xs print:py-3 print:px-2 print:border print:text-black"><?= htmlspecialchars($o['alasan']) ?></td>
                            <td class="px-6 py-6 font-bold text-navy-900 print:py-3 print:px-2 print:border print:text-black"><?= htmlspecialchars($o['nama_lengkap']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($laporan_opname)): ?><tr><td colspan="5" class="text-center py-10 text-slate-400 font-bold">Tidak ada data Opname disetujui bulan ini.</td></tr><?php endif; ?>
                    </tbody>
                </table>
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

<script>
    function stl(t){
        const tabs = ['stok', 'so', 'opname'];
        const titles = {
            'stok': 'Monthly Mutasi Stok | <?= date('M Y') ?>',
            'so': 'Monthly Pengiriman (SO) | <?= date('M Y') ?>',
            'opname': 'Monthly Audit Opname | <?= date('M Y') ?>'
        };
        tabs.forEach(tab => {
            const btn = document.getElementById('btn-'+tab);
            const content = document.getElementById('tab-'+tab);
            if(btn) btn.className="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50";
            if(content) content.classList.add('hidden');
        });
        
        const activeBtn = document.getElementById('btn-'+t);
        const activeContent = document.getElementById('tab-'+t);
        if(activeBtn) activeBtn.className="px-6 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg";
        if(activeContent) activeContent.classList.remove('hidden');
        
        document.getElementById('print-title').innerText = titles[t];
    }
</script>

<?php include 'includes/footer.php'; ?>
