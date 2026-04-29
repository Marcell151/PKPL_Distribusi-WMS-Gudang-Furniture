<?php
require 'config.php';
require_access(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_po = $_POST['id_po'];
    $id_furniture = $_POST['id_furniture'];
    $qty_po = (int)$_POST['qty_po'];
    $qty_fisik = (int)$_POST['qty_fisik'];
    $keterangan_refund = $_POST['keterangan_refund'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // Update Stok
        $stmt = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = stok_tersedia + ? WHERE id_furniture = ?");
        $stmt->execute([$qty_fisik, $id_furniture]);
        
        // Ambil No PO
        $stmt_po = $pdo->prepare("SELECT no_po FROM tb_purchase_order WHERE id_po = ?");
        $stmt_po->execute([$id_po]);
        $no_po = $stmt_po->fetchColumn();
        
        // Catat Mutasi
        $stmt = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan, id_user) VALUES (?, datetime('now', 'localtime'), 'IN', ?, ?, ?)");
        $stmt->execute([$id_furniture, $qty_fisik, "Penerimaan PO: $no_po", $_SESSION['user']['id_user']]);
        
        // Handle Selisih
        if ($qty_fisik < $qty_po) {
            $qty_kurang = $qty_po - $qty_fisik;
            $stmt = $pdo->prepare("INSERT INTO tb_nota_selisih (no_po_supplier, id_furniture, qty_kurang, keterangan_refund) VALUES (?, ?, ?, ?)");
            $stmt->execute([$no_po, $id_furniture, $qty_kurang, $keterangan_refund]);
        }
        
        // Update Status PO jadi Selesai
        $stmt = $pdo->prepare("UPDATE tb_purchase_order SET status = 'Selesai' WHERE id_po = ?");
        $stmt->execute([$id_po]);

        $pdo->commit();
        $success = "Barang dari PO #$no_po berhasil masuk gudang!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Pending POs
$stmt = $pdo->query("
    SELECT p.*, s.nama_supplier, d.id_furniture, d.qty_dipesan, f.nama_barang, f.kode_barang, f.id_lokasi, l.nama_blok, l.rak 
    FROM tb_purchase_order p 
    JOIN tb_detail_po d ON p.id_po = d.id_po 
    JOIN tb_furniture f ON d.id_furniture = f.id_furniture 
    JOIN tb_supplier s ON p.id_supplier = s.id_supplier 
    LEFT JOIN tb_lokasi l ON f.id_lokasi = l.id_lokasi
    WHERE p.status = 'Menunggu Pengiriman' 
    ORDER BY p.tanggal_po ASC
");
$pending_pos = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="mb-10">
        <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Inbound (Penerimaan Barang)</h2>
        <p class="text-slate-500 font-medium mt-1">Proses fisik kedatangan furniture berdasarkan dokumen Purchase Order.</p>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if(empty($pending_pos)): ?>
            <div class="col-span-full py-20 text-center text-slate-400 font-bold uppercase tracking-widest bg-white rounded-[2rem] border border-dashed border-slate-200">Tidak ada Purchase Order yang menunggu pengiriman.</div>
        <?php else: foreach($pending_pos as $p): ?>
        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 card-hover flex flex-col">
            <div class="flex justify-between items-start mb-6">
                <span class="bg-blue-600 text-white px-4 py-1 rounded-full text-[10px] font-black tracking-widest"><?= $p['no_po'] ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= date('d M Y', strtotime($p['tanggal_po'])) ?></span>
            </div>
            <h4 class="text-lg font-black text-navy-900 mb-1"><?= htmlspecialchars($p['nama_supplier']) ?></h4>
            <p class="text-xs text-slate-500 mb-6 font-medium">Order: <span class="font-bold text-blue-600"><?= $p['qty_dipesan'] ?> Unit</span></p>
            
            <div class="bg-slate-50 rounded-2xl p-4 mb-6 space-y-3">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-navy-900 rounded-xl flex items-center justify-center text-white font-black text-xs"><?= substr($p['kode_barang'],0,2) ?></div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none"><?= $p['kode_barang'] ?></p>
                        <p class="text-xs font-bold text-navy-900 truncate"><?= $p['nama_barang'] ?></p>
                    </div>
                </div>
                <div class="text-[10px] font-bold text-blue-600 bg-white px-3 py-2 rounded-lg border border-blue-50">Putaway ke: <?= htmlspecialchars($p['nama_blok'] ?? 'N/A') ?> - <?= htmlspecialchars($p['rak'] ?? 'N/A') ?></div>
            </div>

            <button onclick='openInbound(<?= json_encode($p) ?>)' class="mt-auto w-full py-4 rounded-2xl bg-navy-900 text-white font-black text-xs shadow-lg shadow-navy-900/10 hover:bg-navy-800 transition-all uppercase tracking-widest">Terima Barang</button>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Modal Inbound -->
<div id="mInbound" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg mx-4 overflow-hidden animate-fade-in">
        <div class="px-10 py-8 border-b border-slate-50 bg-blue-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-blue-900">Validasi Inbound</h3>
            <button onclick="closeModal()" class="text-blue-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <input type="hidden" name="id_po" id="mIdPo">
            <input type="hidden" name="id_furniture" id="mIdF">
            <input type="hidden" name="qty_po" id="mQtyPo">
            
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Barang</p>
                <p class="font-black text-navy-900" id="mLabelBarang"></p>
                <div class="mt-4 flex justify-between text-sm">
                    <span class="text-slate-500">Qty di Dokumen PO:</span>
                    <span class="font-black text-blue-600" id="mQtyText"></span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Qty Aktual yang Diterima</label>
                <input type="number" name="qty_fisik" id="mQtyFisik" min="0" required class="w-full bg-slate-50 rounded-2xl p-5 text-2xl font-black text-navy-900 outline-none focus:ring-4 focus:ring-blue-100">
            </div>

            <div id="refund_section" class="hidden p-6 rounded-2xl bg-red-50 border border-red-100 animate-fade-in">
                <label class="block text-[10px] font-bold text-red-400 mb-2 uppercase tracking-widest">Alasan Nota Selisih (Kekurangan)</label>
                <textarea name="keterangan_refund" id="mRefund" rows="2" placeholder="Barang pecah / kurang kirim..." class="w-full bg-white rounded-xl p-4 text-sm font-medium text-red-900 outline-none"></textarea>
            </div>

            <button type="submit" class="w-full py-5 rounded-2xl bg-blue-600 text-white font-black text-sm shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all uppercase tracking-widest">Konfirmasi Terima</button>
        </form>
    </div>
</div>

<script>
    const m = document.getElementById('mInbound'), rs = document.getElementById('refund_section'), mqf = document.getElementById('mQtyFisik');
    let targetQty = 0;

    function openInbound(p) {
        document.getElementById('mIdPo').value = p.id_po;
        document.getElementById('mIdF').value = p.id_furniture;
        document.getElementById('mQtyPo').value = p.qty_dipesan;
        document.getElementById('mLabelBarang').innerText = p.kode_barang + ' - ' + p.nama_barang;
        document.getElementById('mQtyText').innerText = p.qty_dipesan + ' Unit';
        targetQty = parseInt(p.qty_dipesan);
        mqf.value = p.qty_dipesan;
        m.classList.remove('hidden');
        checkDiff();
    }

    function checkDiff() {
        const val = parseInt(mqf.value) || 0;
        if(val < targetQty) {
            rs.classList.remove('hidden');
            document.getElementById('mRefund').required = true;
        } else {
            rs.classList.add('hidden');
            document.getElementById('mRefund').required = false;
        }
    }

    mqf.addEventListener('input', checkDiff);
    function closeModal() { m.classList.add('hidden'); }
</script>

<?php include 'includes/footer.php'; ?>
