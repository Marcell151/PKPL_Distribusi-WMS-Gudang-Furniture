<?php
require 'config.php';
require_access(['Admin', 'Supervisor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_toko = $_POST['id_toko'];
    $tanggal_so = $_POST['tanggal_so'];
    $items = $_POST['items']; // Array of [id_furniture, qty]
    
    $no_so = "SO-" . date('Ymd', strtotime($tanggal_so)) . "-" . strtoupper(substr(uniqid(), -4));

    try {
        $pdo->beginTransaction();
        
        // Validasi Stok terlebih dahulu
        foreach ($items as $item) {
            if ($item['qty'] > 0) {
                $stmt_check = $pdo->prepare("SELECT stok_tersedia, nama_barang FROM tb_furniture WHERE id_furniture = ?");
                $stmt_check->execute([$item['id_furniture']]);
                $furn = $stmt_check->fetch();
                
                if ($furn['stok_tersedia'] < $item['qty']) {
                    throw new Exception("Stok untuk '" . $furn['nama_barang'] . "' tidak mencukupi (Tersedia: " . $furn['stok_tersedia'] . ").");
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO tb_sales_order (no_so, id_toko, tanggal_request, status, id_user) VALUES (?, ?, ?, 'Pending', ?)");
        $stmt->execute([$no_so, $id_toko, $tanggal_so, $_SESSION['user']['id_user']]);
        $id_so = $pdo->lastInsertId();

        $stmt_item = $pdo->prepare("INSERT INTO tb_detail_so (id_so, id_furniture, qty_diminta) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            if ($item['qty'] > 0) {
                $stmt_item->execute([$id_so, $item['id_furniture'], $item['qty']]);
            }
        }

        $pdo->commit();
        $success = "Sales Order #$no_so berhasil diterbitkan!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$tokos = $pdo->query("SELECT * FROM tb_toko ORDER BY nama_toko ASC")->fetchAll();
$furniture = $pdo->query("SELECT * FROM tb_furniture WHERE stok_tersedia > 0 ORDER BY nama_barang ASC")->fetchAll();

$sos = $pdo->query("SELECT s.*, t.nama_toko, u.nama_lengkap FROM tb_sales_order s JOIN tb_toko t ON s.id_toko = t.id_toko LEFT JOIN tb_users u ON s.id_user = u.id_user ORDER BY s.id_so DESC LIMIT 20")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Sales Order (Penjualan)</h2>
            <p class="text-slate-500 font-medium mt-1">Dokumentasi transaksi pesanan dari pelanggan / toko cabang.</p>
        </div>
        <button onclick="document.getElementById('mAdd').classList.remove('hidden')" class="bg-amber-500 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all">Buat SO Baru</button>
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
                    <th class="px-8 py-6">No. SO</th>
                    <th class="px-8 py-6">Tanggal</th>
                    <th class="px-8 py-6">Customer / Toko</th>
                    <th class="px-8 py-6">Dibuat Oleh</th>
                    <th class="px-8 py-6 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach($sos as $s): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-8 py-6 font-black text-navy-900"><?= $s['no_so'] ?></td>
                    <td class="px-8 py-6 font-bold text-slate-600"><?= date('d/m/Y', strtotime($s['tanggal_request'])) ?></td>
                    <td class="px-8 py-6 font-bold text-amber-600"><?= htmlspecialchars($s['nama_toko']) ?></td>
                    <td class="px-8 py-6 text-slate-500"><?= htmlspecialchars($s['nama_lengkap'] ?? 'Sistem') ?></td>
                    <td class="px-8 py-6 text-center">
                        <?php 
                            $sc = 'bg-slate-100 text-slate-700';
                            if($s['status'] == 'Pending') $sc = 'bg-amber-100 text-amber-700';
                            elseif($s['status'] == 'Shipped') $sc = 'bg-green-100 text-green-700';
                            else $sc = 'bg-blue-100 text-blue-700';
                        ?>
                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $sc ?>"><?= str_replace('_', ' ', $s['status']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="mAdd" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50 overflow-y-auto py-10">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl mx-4 overflow-hidden animate-fade-in my-auto">
        <div class="px-10 py-8 border-b border-slate-50 bg-amber-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-amber-900">Form Sales Order</h3>
            <button onclick="document.getElementById('mAdd').classList.add('hidden')" class="text-amber-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-8">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Pilih Customer</label>
                    <select name="id_toko" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none appearance-none cursor-pointer">
                        <?php foreach($tokos as $t): ?>
                            <option value="<?= $t['id_toko'] ?>"><?= htmlspecialchars($t['nama_toko']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Tanggal SO</label>
                    <input type="date" name="tanggal_so" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none">
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Daftar Barang & Stok Tersedia</label>
                <div class="max-h-60 overflow-y-auto space-y-2 pr-2">
                    <?php if(empty($furniture)): ?>
                        <p class="text-center py-10 text-slate-400 italic">Maaf, stok semua barang sedang kosong.</p>
                    <?php else: foreach($furniture as $f): ?>
                    <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl">
                        <div class="flex-1">
                            <p class="text-xs font-black text-navy-900"><?= $f['kode_barang'] ?></p>
                            <p class="text-[10px] text-slate-500"><?= htmlspecialchars($f['nama_barang']) ?></p>
                            <p class="text-[9px] font-bold text-amber-600 uppercase">Tersedia: <?= $f['stok_tersedia'] ?> Unit</p>
                        </div>
                        <input type="hidden" name="items[<?= $f['id_furniture'] ?>][id_furniture]" value="<?= $f['id_furniture'] ?>">
                        <input type="number" name="items[<?= $f['id_furniture'] ?>][qty]" min="0" max="<?= $f['stok_tersedia'] ?>" value="0" class="w-20 bg-white border border-slate-200 rounded-xl p-2 text-center text-sm font-bold text-amber-600 outline-none">
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <button type="submit" <?= empty($furniture) ? 'disabled' : '' ?> class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed">Terbitkan Sales Order</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
