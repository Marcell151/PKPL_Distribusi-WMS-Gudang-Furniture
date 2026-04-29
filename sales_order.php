<?php
require 'config.php';
require_access(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_toko = $_POST['id_toko']; $id_f = $_POST['id_f']; $qty = (int)$_POST['qty'];
    $no_so = "SO-" . date('Ymd') . "-" . rand(1000,9999);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO tb_sales_order (no_so, id_toko, tanggal_request, status, id_user) VALUES (?, ?, date('now'), 'Pending', ?)");
        $stmt->execute([$no_so, $id_toko, $_SESSION['user']['id_user']]);
        $id_so = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO tb_detail_so (id_so, id_furniture, qty_diminta) VALUES (?, ?, ?)");
        $stmt->execute([$id_so, $id_f, $qty]);
        $pdo->commit();
        $success = "SO #$no_so Berhasil dibuat!";
    } catch (PDOException $e) { $pdo->rollBack(); $error = $e->getMessage(); }
}

$stmt = $pdo->query("SELECT * FROM tb_furniture ORDER BY nama_barang ASC");
$furns = $stmt->fetchAll();
$stmt = $pdo->query("SELECT * FROM tb_toko ORDER BY nama_toko ASC");
$tokos = $stmt->fetchAll();
$stmt = $pdo->query("SELECT s.*, t.nama_toko, t.kode_toko, d.qty_diminta, f.nama_barang, f.kode_barang FROM tb_sales_order s JOIN tb_detail_so d ON s.id_so = d.id_so JOIN tb_furniture f ON d.id_furniture = f.id_furniture LEFT JOIN tb_toko t ON s.id_toko = t.id_toko ORDER BY s.id_so DESC LIMIT 20");
$sos = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Sales Order</h2>
            <p class="text-slate-500 font-medium mt-1">Permintaan pengiriman furniture dari toko cabang.</p>
        </div>
        <button onclick="document.getElementById('mAdd').classList.remove('hidden')" class="bg-amber-500 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all">Buat SO Baru</button>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">ID / NO SO</th>
                        <th class="px-8 py-6">Toko Cabang</th>
                        <th class="px-8 py-6">Produk</th>
                        <th class="px-8 py-6 text-center">Qty</th>
                        <th class="px-8 py-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($sos as $s): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6"><span class="font-black text-navy-900"><?= $s['no_so'] ?></span></td>
                        <td class="px-8 py-6 font-bold text-slate-600"><?= htmlspecialchars($s['nama_toko'] ?? 'N/A') ?> <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-1 rounded ml-1"><?= htmlspecialchars($s['kode_toko'] ?? '') ?></span></td>
                        <td class="px-8 py-6">
                            <p class="font-black text-navy-900"><?= htmlspecialchars($s['kode_barang']) ?></p>
                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($s['nama_barang']) ?></p>
                        </td>
                        <td class="px-8 py-6 text-center font-black text-lg text-amber-600"><?= $s['qty_diminta'] ?></td>
                        <td class="px-8 py-6 text-center">
                            <?php 
                                $sc = 'bg-slate-100 text-slate-700';
                                if($s['status'] == 'Pending') $sc = 'bg-amber-100 text-amber-700';
                                elseif($s['status'] == 'Picking' || $s['status'] == 'Packing') $sc = 'bg-blue-100 text-blue-700';
                                elseif($s['status'] == 'QC_Passed') $sc = 'bg-purple-100 text-purple-700';
                                elseif($s['status'] == 'Shipped') $sc = 'bg-green-100 text-green-700';
                            ?>
                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $sc ?>"><?= str_replace('_', ' ', $s['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="mAdd" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg mx-4 overflow-hidden animate-fade-in">
        <div class="px-10 py-8 border-b border-slate-50 bg-amber-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-amber-900">Request SO</h3>
            <button onclick="document.getElementById('mAdd').classList.add('hidden')" class="text-amber-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Toko Tujuan</label>
                <select name="id_toko" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none appearance-none cursor-pointer focus:ring-2 focus:ring-amber-500">
                    <option value="" disabled selected>-- Pilih Toko Cabang --</option>
                    <?php foreach($tokos as $t): ?>
                        <option value="<?= $t['id_toko'] ?>"><?= htmlspecialchars($t['kode_toko']) ?> - <?= htmlspecialchars($t['nama_toko']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Furniture</label>
                <select name="id_f" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none appearance-none cursor-pointer">
                    <?php foreach($furns as $f): ?>
                        <option value="<?= $f['id_furniture'] ?>"><?= $f['kode_barang'] ?> - <?= $f['nama_barang'] ?> (Ready: <?= $f['stok_tersedia'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Jumlah Unit</label>
                <input type="number" name="qty" min="1" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <button type="submit" class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Kirim Permintaan</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
