<?php
require 'config.php';
require_access(['Admin', 'Supervisor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_supplier = $_POST['id_supplier'];
    $tanggal_po = $_POST['tanggal_po'];
    $items = $_POST['items']; // Array of [id_furniture, qty]
    
    $no_po = "PO-" . date('Ymd', strtotime($tanggal_po)) . "-" . strtoupper(substr(uniqid(), -4));

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO tb_purchase_order (no_po, id_supplier, tanggal_po, status, id_user) VALUES (?, ?, ?, 'Menunggu Pengiriman', ?)");
        $stmt->execute([$no_po, $id_supplier, $tanggal_po, $_SESSION['user']['id_user']]);
        $id_po = $pdo->lastInsertId();

        $stmt_item = $pdo->prepare("INSERT INTO tb_detail_po (id_po, id_furniture, qty_dipesan) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            if ($item['qty'] > 0) {
                $stmt_item->execute([$id_po, $item['id_furniture'], $item['qty']]);
            }
        }

        $pdo->commit();
        $success = "Purchase Order #$no_po berhasil diterbitkan!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Gagal membuat PO: " . $e->getMessage();
    }
}

$suppliers = $pdo->query("SELECT * FROM tb_supplier ORDER BY nama_supplier ASC")->fetchAll();
$furniture = $pdo->query("SELECT * FROM tb_furniture ORDER BY nama_barang ASC")->fetchAll();

$pos = $pdo->query("SELECT p.*, s.nama_supplier, u.nama_lengkap FROM tb_purchase_order p JOIN tb_supplier s ON p.id_supplier = s.id_supplier LEFT JOIN tb_users u ON p.id_user = u.id_user ORDER BY p.id_po DESC LIMIT 20")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Purchase Order (Pembelian)</h2>
            <p class="text-slate-500 font-medium mt-1">Dokumentasi transaksi pemesanan barang ke Supplier.</p>
        </div>
        <button onclick="document.getElementById('mAdd').classList.remove('hidden')" class="bg-blue-600 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all">Buat PO Baru</button>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $error ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                    <th class="px-8 py-6">No. PO</th>
                    <th class="px-8 py-6">Tanggal</th>
                    <th class="px-8 py-6">Supplier</th>
                    <th class="px-8 py-6">Dibuat Oleh</th>
                    <th class="px-8 py-6 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach($pos as $p): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-8 py-6 font-black text-navy-900"><?= $p['no_po'] ?></td>
                    <td class="px-8 py-6 font-bold text-slate-600"><?= date('d/m/Y', strtotime($p['tanggal_po'])) ?></td>
                    <td class="px-8 py-6 font-bold text-blue-600"><?= htmlspecialchars($p['nama_supplier']) ?></td>
                    <td class="px-8 py-6 text-slate-500"><?= htmlspecialchars($p['nama_lengkap'] ?? 'Sistem') ?></td>
                    <td class="px-8 py-6 text-center">
                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $p['status'] == 'Selesai' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>"><?= $p['status'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="mAdd" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50 overflow-y-auto py-10">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl mx-4 overflow-hidden animate-fade-in my-auto">
        <div class="px-10 py-8 border-b border-slate-50 bg-blue-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-blue-900">Form Purchase Order</h3>
            <button onclick="document.getElementById('mAdd').classList.add('hidden')" class="text-blue-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-8">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Pilih Supplier</label>
                    <select name="id_supplier" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none appearance-none cursor-pointer">
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Tanggal Order</label>
                    <input type="date" name="tanggal_po" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none">
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Daftar Barang & Qty</label>
                <div class="max-h-60 overflow-y-auto space-y-2 pr-2">
                    <?php foreach($furniture as $f): ?>
                    <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl">
                        <div class="flex-1">
                            <p class="text-xs font-black text-navy-900"><?= $f['kode_barang'] ?></p>
                            <p class="text-[10px] text-slate-500"><?= htmlspecialchars($f['nama_barang']) ?></p>
                        </div>
                        <input type="hidden" name="items[<?= $f['id_furniture'] ?>][id_furniture]" value="<?= $f['id_furniture'] ?>">
                        <input type="number" name="items[<?= $f['id_furniture'] ?>][qty]" min="0" value="0" class="w-20 bg-white border border-slate-200 rounded-xl p-2 text-center text-sm font-bold text-blue-600 outline-none">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="w-full py-5 rounded-2xl bg-blue-600 text-white font-black text-sm shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all uppercase tracking-widest">Terbitkan Purchase Order</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
