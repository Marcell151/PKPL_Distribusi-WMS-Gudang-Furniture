<?php
require 'config.php';
require_access(['Admin', 'Supervisor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_supplier = $_POST['id_supplier']; $id_f = $_POST['id_f']; $qty = (int)$_POST['qty'];
    $no_po = "PO-" . date('Ymd') . "-" . rand(1000,9999);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO tb_purchase_order (no_po, id_supplier, tanggal_po, status, id_user) VALUES (?, ?, date('now'), 'Pending', ?)");
        $stmt->execute([$no_po, $id_supplier, $_SESSION['user']['id_user']]);
        $id_po = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO tb_detail_po (id_po, id_furniture, qty_dipesan) VALUES (?, ?, ?)");
        $stmt->execute([$id_po, $id_f, $qty]);
        $pdo->commit();
        $success = "Purchase Order #$no_po Berhasil dibuat!";
    } catch (PDOException $e) { $pdo->rollBack(); $error = $e->getMessage(); }
}

$stmt = $pdo->query("SELECT * FROM tb_furniture ORDER BY nama_barang ASC");
$furns = $stmt->fetchAll();
$stmt = $pdo->query("SELECT * FROM tb_supplier ORDER BY nama_supplier ASC");
$suppliers = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.*, s.nama_supplier, d.qty_dipesan, f.nama_barang, f.kode_barang, u.nama_lengkap as pembuat 
    FROM tb_purchase_order p 
    JOIN tb_detail_po d ON p.id_po = d.id_po 
    JOIN tb_furniture f ON d.id_furniture = f.id_furniture 
    LEFT JOIN tb_supplier s ON p.id_supplier = s.id_supplier 
    LEFT JOIN tb_users u ON p.id_user = u.id_user
    ORDER BY p.id_po DESC LIMIT 20
");
$pos = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Purchase Order</h2>
            <p class="text-slate-500 font-medium mt-1">Permintaan pengadaan barang ke Supplier.</p>
        </div>
        <button onclick="document.getElementById('mAdd').classList.remove('hidden')" class="bg-blue-600 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all">Buat PO Baru</button>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">NO PO</th>
                        <th class="px-8 py-6">Supplier</th>
                        <th class="px-8 py-6">Produk</th>
                        <th class="px-8 py-6 text-center">Qty</th>
                        <th class="px-8 py-6 text-center">Dipesan Oleh</th>
                        <th class="px-8 py-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($pos as $p): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6"><span class="font-black text-navy-900"><?= htmlspecialchars($p['no_po']) ?></span></td>
                        <td class="px-8 py-6 font-bold text-slate-600"><?= htmlspecialchars($p['nama_supplier'] ?? 'N/A') ?></td>
                        <td class="px-8 py-6">
                            <p class="font-black text-navy-900"><?= htmlspecialchars($p['kode_barang']) ?></p>
                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($p['nama_barang']) ?></p>
                        </td>
                        <td class="px-8 py-6 text-center font-black text-lg text-blue-600"><?= $p['qty_dipesan'] ?></td>
                        <td class="px-8 py-6 text-center text-slate-500 font-bold text-xs"><?= htmlspecialchars($p['pembuat'] ?? 'Sistem') ?></td>
                        <td class="px-8 py-6 text-center">
                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $p['status'] == 'Pending' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' ?>"><?= $p['status'] ?></span>
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
        <div class="px-10 py-8 border-b border-slate-50 bg-blue-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-blue-900">Request PO</h3>
            <button onclick="document.getElementById('mAdd').classList.add('hidden')" class="text-blue-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Supplier Tujuan</label>
                <select name="id_supplier" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none appearance-none cursor-pointer focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled selected>-- Pilih Supplier --</option>
                    <?php foreach($suppliers as $s): ?>
                        <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Furniture</label>
                <select name="id_f" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none appearance-none cursor-pointer focus:ring-2 focus:ring-blue-500">
                    <?php foreach($furns as $f): ?>
                        <option value="<?= $f['id_furniture'] ?>"><?= $f['kode_barang'] ?> - <?= $f['nama_barang'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Jumlah Unit</label>
                <input type="number" name="qty" min="1" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full py-5 rounded-2xl bg-blue-600 text-white font-black text-sm shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all uppercase tracking-widest">Kirim PO</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
