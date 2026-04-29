<?php
require 'config.php';
require_access(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_so = $_POST['id_so'];
    $no_so = $_POST['no_so'];
    
    try {
        $pdo->beginTransaction();
        if ($_POST['action'] === 'picking') {
            $stmt = $pdo->prepare("UPDATE tb_sales_order SET status = 'Picking' WHERE id_so = ?");
            $stmt->execute([$id_so]);
            $success = "SO #$no_so: Mulai proses Picking!";
        } elseif ($_POST['action'] === 'qc_process') {
            $id_f = $_POST['id_f']; $qty = (int)$_POST['qty']; $kep = $_POST['kep']; $ket = $_POST['ket'] ?? '';
            
            if ($kep === 'lolos') {
                $stmt = $pdo->prepare("UPDATE tb_sales_order SET status = 'QC_Passed' WHERE id_so = ?");
                $stmt->execute([$id_so]);
                $success = "SO #$no_so: Lolos QC!";
            } else {
                // Pindah ke Karantina
                $stmt = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = stok_tersedia - ?, stok_karantina = stok_karantina + ? WHERE id_furniture = ?"); 
                $stmt->execute([$qty, $qty, $id_f]);
                
                $stmt = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan, id_user) VALUES (?, datetime('now'), 'MUTASI_RUSAK', ?, ?, ?)"); 
                $stmt->execute([$id_f, -$qty, "RUSAK (Gagal QC) SO: ".$no_so." - ".$ket, $_SESSION['user']['id_user']]);
                
                $stmt = $pdo->prepare("UPDATE tb_sales_order SET status = 'Pending' WHERE id_so = ?");
                $stmt->execute([$id_so]);
                $error = "SO #$no_so: Gagal QC, barang masuk karantina. SO kembali ke antrean Picking.";
            }
        } elseif ($_POST['action'] === 'packing') {
            $stmt = $pdo->prepare("UPDATE tb_sales_order SET status = 'Packing' WHERE id_so = ?");
            $stmt->execute([$id_so]);
            $success = "SO #$no_so: Selesai Packing!";
        } elseif ($_POST['action'] === 'shipped') {
            $id_f = $_POST['id_f']; $qty = (int)$_POST['qty'];
            
            // Potong Stok Akhir
            $stmt = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = stok_tersedia - ? WHERE id_furniture = ?"); 
            $stmt->execute([$qty, $id_f]);
            
            $stmt = $pdo->prepare("UPDATE tb_sales_order SET status = 'Shipped' WHERE id_so = ?"); 
            $stmt->execute([$id_so]);
            
            $stmt = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan, id_user) VALUES (?, datetime('now'), 'OUT', ?, ?, ?)"); 
            $stmt->execute([$id_f, -$qty, "Kirim SO: ".$no_so, $_SESSION['user']['id_user']]);
            
            $success = "SO #$no_so: Berhasil dikirim! Stok gudang telah dipotong.";
        }
        $pdo->commit();
    } catch (Exception $e) { $pdo->rollBack(); $error = $e->getMessage(); }
}

$stmt = $pdo->query("SELECT s.*, t.nama_toko, d.qty_diminta, f.id_furniture, f.nama_barang, f.kode_barang, f.stok_tersedia, l.nama_blok, l.rak FROM tb_sales_order s JOIN tb_detail_so d ON s.id_so = d.id_so JOIN tb_furniture f ON d.id_furniture = f.id_furniture JOIN tb_toko t ON s.id_toko = t.id_toko LEFT JOIN tb_lokasi l ON f.id_lokasi = l.id_lokasi WHERE s.status != 'Shipped' ORDER BY s.id_so ASC");
$sos = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="mb-10">
        <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Outbound & QC (Distribusi)</h2>
        <p class="text-slate-500 font-medium mt-1">Eksekusi Sales Order: Inspeksi kualitas dan pengiriman unit ke customer.</p>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $error ?></div>
    <?php endif; ?>

    <div class="flex flex-wrap gap-2 mb-10 bg-slate-200 p-2 rounded-2xl w-fit">
        <button id="btn-Pending" onclick="st('Pending')" class="px-6 py-3 rounded-xl font-black text-[10px] transition-all bg-navy-900 text-white shadow-lg relative uppercase tracking-widest">1. Picking List <span class="absolute -top-2 -right-2 bg-amber-500 text-white text-[8px] w-5 h-5 flex items-center justify-center rounded-full"><?= count(array_filter($sos, fn($x) => $x['status'] == 'Pending')) ?></span></button>
        <button id="btn-Picking" onclick="st('Picking')" class="px-6 py-3 rounded-xl font-black text-[10px] transition-all text-slate-600 hover:bg-white/50 relative uppercase tracking-widest">2. Proses QC <span class="absolute -top-2 -right-2 bg-blue-500 text-white text-[8px] w-5 h-5 flex items-center justify-center rounded-full"><?= count(array_filter($sos, fn($x) => $x['status'] == 'Picking')) ?></span></button>
        <button id="btn-QC_Passed" onclick="st('QC_Passed')" class="px-6 py-3 rounded-xl font-black text-[10px] transition-all text-slate-600 hover:bg-white/50 relative uppercase tracking-widest">3. Packing <span class="absolute -top-2 -right-2 bg-purple-500 text-white text-[8px] w-5 h-5 flex items-center justify-center rounded-full"><?= count(array_filter($sos, fn($x) => $x['status'] == 'QC_Passed')) ?></span></button>
        <button id="btn-Packing" onclick="st('Packing')" class="px-6 py-3 rounded-xl font-black text-[10px] transition-all text-slate-600 hover:bg-white/50 relative uppercase tracking-widest">4. Siap Kirim <span class="absolute -top-2 -right-2 bg-green-500 text-white text-[8px] w-5 h-5 flex items-center justify-center rounded-full"><?= count(array_filter($sos, fn($x) => $x['status'] == 'Packing')) ?></span></button>
    </div>

    <?php foreach(['Pending', 'Picking', 'QC_Passed', 'Packing'] as $status): ?>
    <div id="tab-<?= $status ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in <?= $status !== 'Pending' ? 'hidden' : '' ?>">
        <?php $filtered = array_filter($sos, fn($x) => $x['status'] == $status); if(empty($filtered)): ?>
            <div class="col-span-full py-20 text-center text-slate-400 font-bold uppercase tracking-widest bg-white rounded-[2rem] border border-dashed border-slate-200">Antrean <?= $status ?> Kosong</div>
        <?php else: foreach($filtered as $s): ?>
        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 card-hover flex flex-col">
            <div class="flex justify-between items-start mb-6">
                <span class="bg-navy-900 text-white px-4 py-1 rounded-full text-[10px] font-black tracking-widest"><?= $s['no_so'] ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= date('d M Y', strtotime($s['tanggal_request'])) ?></span>
            </div>
            <h4 class="text-lg font-black text-navy-900 mb-1"><?= htmlspecialchars($s['nama_toko']) ?></h4>
            <p class="text-xs text-slate-500 mb-6 font-medium">Request: <span class="font-bold text-navy-900"><?= $s['qty_diminta'] ?> Unit</span></p>
            
            <div class="bg-slate-50 rounded-2xl p-4 mb-6 flex flex-col gap-2">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-white font-black text-xs"><?= substr($s['kode_barang'],0,2) ?></div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none"><?= $s['kode_barang'] ?></p>
                        <p class="text-xs font-bold text-navy-900 truncate"><?= $s['nama_barang'] ?></p>
                    </div>
                </div>
                <div class="mt-2 text-xs font-bold text-blue-600 bg-blue-50 px-3 py-2 rounded-lg italic">Ambil di: <?= htmlspecialchars($s['nama_blok'] ?? 'N/A') ?> - <?= htmlspecialchars($s['rak'] ?? 'N/A') ?></div>
            </div>

            <form method="POST" action="outbound.php" class="mt-auto">
                <input type="hidden" name="id_so" value="<?= $s['id_so'] ?>">
                <input type="hidden" name="no_so" value="<?= $s['no_so'] ?>">
                <input type="hidden" name="id_f" value="<?= $s['id_furniture'] ?>">
                <input type="hidden" name="qty" value="<?= $s['qty_diminta'] ?>">
                <?php if($status === 'Pending'): ?>
                    <input type="hidden" name="action" value="picking">
                    <button type="submit" class="w-full py-4 rounded-2xl bg-navy-900 text-white font-black text-xs shadow-lg shadow-navy-900/10 hover:bg-navy-800 transition-all uppercase tracking-widest">Mulai Picking</button>
                <?php elseif($status === 'Picking'): ?>
                    <button type="button" onclick='oqc(<?= json_encode($s) ?>)' class="w-full py-4 rounded-2xl bg-amber-500 text-white font-black text-xs shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Inspeksi QC</button>
                <?php elseif($status === 'QC_Passed'): ?>
                    <input type="hidden" name="action" value="packing">
                    <button type="submit" class="w-full py-4 rounded-2xl bg-purple-600 text-white font-black text-xs shadow-lg shadow-purple-600/20 hover:bg-purple-700 transition-all uppercase tracking-widest">Selesai Packing</button>
                <?php elseif($status === 'Packing'): ?>
                    <input type="hidden" name="action" value="shipped">
                    <button type="submit" class="w-full py-4 rounded-2xl bg-green-600 text-white font-black text-xs shadow-lg shadow-green-600/20 hover:bg-green-700 transition-all uppercase tracking-widest flex items-center justify-center gap-2">Cetak Surat Jalan & Kirim</button>
                <?php endif; ?>
            </form>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<div id="mqc" class="fixed inset-0 bg-navy-900/70 backdrop-blur-md flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-2xl mx-4 overflow-hidden animate-fade-in">
        <div class="p-10 border-b border-slate-50 flex justify-between items-center">
            <h3 class="text-2xl font-black text-navy-900 tracking-tight">Quality Inspection</h3>
            <button onclick="cqc()" class="text-slate-400 text-3xl">&times;</button>
        </div>
        <form method="POST" id="fqc" class="p-10 space-y-10">
            <input type="hidden" name="action" value="qc_process">
            <input type="hidden" name="id_so" id="q_id_so">
            <input type="hidden" name="no_so" id="q_no_so">
            <input type="hidden" name="id_f" id="q_id_f">
            <input type="hidden" name="qty" id="q_qty">
            <input type="hidden" name="kep" id="q_kep">
            
            <div class="grid grid-cols-2 gap-8 text-center bg-slate-50 rounded-[2rem] p-8 border border-slate-100">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Produk</p>
                    <p class="font-black text-navy-900 text-lg leading-tight" id="lb"></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Target Kirim</p>
                    <p class="font-black text-amber-500 text-2xl" id="lq"></p>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Checklist Kondisi Fisik</label>
                <textarea name="ket" id="q_ket" rows="2" placeholder="Catatan inspeksi fisik (Wajib diisi jika gagal QC)..." class="w-full bg-slate-50 rounded-2xl p-6 text-sm font-medium outline-none focus:ring-2 focus:ring-navy-900"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <button type="button" onclick="sqc('lolos')" class="flex flex-col items-center justify-center py-8 rounded-[2rem] bg-[#10b981] text-white shadow-lg shadow-green-500/30 hover:scale-[1.02] transition-all border-4 border-white">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    <span class="font-black text-xl uppercase tracking-tighter">Lolos QC</span>
                </button>
                
                <button type="button" onclick="sqc('gagal')" class="flex flex-col items-center justify-center py-8 rounded-[2rem] bg-[#ef4444] text-white shadow-lg shadow-red-500/30 hover:scale-[1.02] transition-all border-4 border-white">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span class="font-black text-xl uppercase tracking-tighter">Gagal QC</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function st(t){
        const tabs = ['Pending', 'Picking', 'QC_Passed', 'Packing'];
        tabs.forEach(tab => {
            const btn = document.getElementById('btn-'+tab);
            const content = document.getElementById('tab-'+tab);
            if(btn) btn.className="px-6 py-3 rounded-xl font-black text-[10px] transition-all text-slate-600 hover:bg-white/50 relative uppercase tracking-widest";
            if(content) content.classList.add('hidden');
        });
        
        const activeBtn = document.getElementById('btn-'+t);
        const activeContent = document.getElementById('tab-'+t);
        if(activeBtn) activeBtn.className="px-6 py-3 rounded-xl font-black text-[10px] transition-all bg-navy-900 text-white shadow-lg relative uppercase tracking-widest";
        if(activeContent) activeContent.classList.remove('hidden');
    }
    
    function oqc(d){ document.getElementById('q_id_so').value=d.id_so; document.getElementById('q_no_so').value=d.no_so; document.getElementById('q_id_f').value=d.id_furniture; document.getElementById('q_qty').value=d.qty_diminta; document.getElementById('lb').innerText=d.nama_barang; document.getElementById('lq').innerText=d.qty_diminta+' Unit'; document.getElementById('mqc').classList.remove('hidden'); }
    function cqc(){ document.getElementById('mqc').classList.add('hidden'); }
    function sqc(k){ if(k==='gagal'&&!document.getElementById('q_ket').value.trim()){ alert('Alasan gagal wajib diisi!'); return; } document.getElementById('q_kep').value=k; document.getElementById('fqc').submit(); }
</script>

<?php include 'includes/footer.php'; ?>
