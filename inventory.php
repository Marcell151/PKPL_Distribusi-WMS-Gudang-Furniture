<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'opname') {
    require_access(['Admin', 'Supervisor']);
    $id_f = $_POST['id_furniture'];
    $qty_fisik = (int)$_POST['qty_fisik_opname'];
    $keterangan = $_POST['keterangan_opname'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT stok_tersedia FROM tb_furniture WHERE id_furniture = ?");
        $stmt->execute([$id_f]);
        $stok_sistem = $stmt->fetchColumn();
        $selisih = $qty_fisik - $stok_sistem;
        if ($selisih != 0) {
            $stmt = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = ? WHERE id_furniture = ?");
            $stmt->execute([$qty_fisik, $id_f]);
            $stmt = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan) VALUES (?, datetime('now', 'localtime'), 'ADJUST_OPNAME', ?, ?)");
            $stmt->execute([$id_f, $selisih, "Opname: " . $keterangan]);
        }
        $pdo->commit();
        $success_opname = "Opname tersimpan!";
    } catch (PDOException $e) { $pdo->rollBack(); $error_opname = $e->getMessage(); }
}

$stmt = $pdo->query("SELECT * FROM tb_furniture ORDER BY nama_barang ASC");
$furniture_list = $stmt->fetchAll();

$where = ""; $params = [];
if (isset($_GET['filter_barang']) && !empty($_GET['filter_barang'])) {
    $where = "WHERE m.id_furniture = ?";
    $params[] = $_GET['filter_barang'];
}

$stmt = $pdo->prepare("SELECT m.*, f.nama_barang, f.kode_barang FROM tb_mutasi_stok m JOIN tb_furniture f ON m.id_furniture = f.id_furniture $where ORDER BY m.id_mutasi DESC LIMIT 100");
$stmt->execute($params);
$mutasi_list = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="mb-10">
        <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Logistik & Inventory</h2>
        <p class="text-slate-500 font-medium mt-1">Audit mutasi stok dan rekonsiliasi fisik gudang.</p>
    </header>

    <div class="flex gap-2 mb-10 bg-slate-200 p-2 rounded-2xl w-fit">
        <button id="btn-kartu" onclick="st('k')" class="px-8 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg">Kartu Stok</button>
        <?php if (check_access(['Admin', 'Supervisor'])): ?>
        <button id="btn-opname" onclick="st('o')" class="px-8 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50">Stock Opname</button>
        <?php endif; ?>
    </div>

    <div id="tk" class="space-y-6">
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 mb-8 flex flex-wrap gap-4 items-end">
            <form method="GET" class="flex flex-1 gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Filter Produk</label>
                    <select name="filter_barang" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="">-- Semua Furniture --</option>
                        <?php foreach($furniture_list as $f): ?>
                            <option value="<?= $f['id_furniture'] ?>" <?= (isset($_GET['filter_barang']) && $_GET['filter_barang'] == $f['id_furniture']) ? 'selected' : '' ?>><?= htmlspecialchars($f['kode_barang']) ?> - <?= htmlspecialchars($f['nama_barang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-navy-900 text-white px-8 py-4 rounded-2xl font-bold text-sm shadow-xl shadow-navy-900/10">Filter</button>
            </form>
        </div>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                            <th class="px-8 py-6">Tanggal</th>
                            <th class="px-8 py-6">Barang</th>
                            <th class="px-8 py-6">Status</th>
                            <th class="px-8 py-6 text-center">Qty</th>
                            <th class="px-8 py-6">Memo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        <?php foreach($mutasi_list as $m): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6 text-slate-500 font-medium"><?= date('d M Y, H:i', strtotime($m['tgl_mutasi'])) ?></td>
                            <td class="px-8 py-6">
                                <p class="font-bold text-navy-900"><?= htmlspecialchars($m['kode_barang']) ?></p>
                                <p class="text-[10px] text-slate-400 truncate w-40"><?= htmlspecialchars($m['nama_barang']) ?></p>
                            </td>
                            <td class="px-8 py-6">
                                <?php 
                                    $c = ''; switch($m['jenis_mutasi']){
                                        case 'IN': $c = 'bg-blue-100 text-blue-600'; break;
                                        case 'OUT': $c = 'bg-green-100 text-green-600'; break;
                                        case 'MUTASI_RUSAK': $c = 'bg-red-100 text-red-600'; break;
                                        default: $c = 'bg-amber-100 text-amber-600';
                                    }
                                ?>
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter <?= $c ?>"><?= $m['jenis_mutasi'] ?></span>
                            </td>
                            <td class="px-8 py-6 text-center font-black text-lg <?= $m['qty'] > 0 ? 'text-blue-600' : 'text-red-600' ?>"><?= ($m['qty'] > 0 ? '+' : '') . $m['qty'] ?></td>
                            <td class="px-8 py-6 text-slate-500 italic"><?= htmlspecialchars($m['keterangan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (check_access(['Admin', 'Supervisor'])): ?>
    <div id="to" class="hidden animate-fade-in">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-12 max-w-2xl">
            <h3 class="text-2xl font-black text-navy-900 mb-8">Form Stock Opname</h3>
            <form method="POST" action="inventory.php" class="space-y-8">
                <input type="hidden" name="action" value="opname">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Produk Audit</label>
                    <select name="id_furniture" id="idfo" required onchange="u()" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-amber-500 outline-none appearance-none">
                        <option value="" disabled selected>-- Pilih Furniture --</option>
                        <?php foreach($furniture_list as $f): ?>
                            <option value="<?= $f['id_furniture'] ?>" data-s="<?= $f['stok_tersedia'] ?>"><?= htmlspecialchars($f['kode_barang']) ?> - <?= htmlspecialchars($f['nama_barang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Stok Sistem</label>
                        <input type="text" id="ssd" disabled class="w-full bg-slate-100 border-none rounded-2xl p-5 text-xl font-black text-slate-400" value="-">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Qty Fisik</label>
                        <input type="number" name="qty_fisik_opname" required class="w-full bg-amber-50 border-none rounded-2xl p-5 text-xl font-black text-amber-600 focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Alasan Penyesuaian</label>
                    <textarea name="keterangan_opname" required rows="2" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-medium text-navy-900 focus:ring-2 focus:ring-amber-500 outline-none" placeholder="Alasan selisih stok..."></textarea>
                </div>
                <button type="submit" class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-2xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Simpan Opname</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function st(t){
        const tk=document.getElementById('tk'), to=document.getElementById('to'), bk=document.getElementById('btn-kartu'), bo=document.getElementById('btn-opname');
        if(t==='k'){ 
            tk.classList.remove('hidden'); if(to) to.classList.add('hidden'); 
            bk.className="px-8 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg"; 
            if(bo) bo.className="px-8 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50"; 
        }
        else { 
            tk.classList.add('hidden'); to.classList.remove('hidden'); 
            bk.className="px-8 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50"; 
            bo.className="px-8 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg"; 
        }
    }
    function u(){ const s=document.getElementById('idfo'), d=document.getElementById('ssd'), o=s.options[s.selectedIndex]; d.value=o?o.getAttribute('data-s'):'-'; }
</script>

<?php include 'includes/footer.php'; ?>
