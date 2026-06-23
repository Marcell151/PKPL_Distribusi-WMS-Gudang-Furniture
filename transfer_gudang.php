<?php
require 'config.php';
require_access(['Admin', 'Staff Gudang', 'Supervisor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_furniture = $_POST['id_furniture'];
    $id_gudang_asal = $_POST['id_gudang_asal'];
    $id_gudang_tujuan = $_POST['id_gudang_tujuan'];
    $qty = (int)$_POST['qty'];
    $keterangan = $_POST['keterangan'];
    $id_user = $_SESSION['user']['id_user'];

    if ($id_gudang_asal === $id_gudang_tujuan) {
        $error = "Gudang asal dan tujuan tidak boleh sama!";
    } else {
        try {
            $pdo->beginTransaction();

            // Cek stok tersedia
            $stmt = $pdo->prepare("SELECT stok_tersedia FROM tb_furniture WHERE id_furniture = ?");
            $stmt->execute([$id_furniture]);
            $stok = $stmt->fetchColumn();

            if ($qty > $stok) {
                throw new Exception("Qty transfer melebihi stok tersedia ($stok)!");
            }

            // Transfer Out
            $stmt_out = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan, id_user, id_gudang_asal, id_gudang_tujuan) VALUES (?, datetime('now', 'localtime'), 'TRANSFER_OUT', ?, ?, ?, ?, ?)");
            $stmt_out->execute([$id_furniture, -$qty, $keterangan, $id_user, $id_gudang_asal, $id_gudang_tujuan]);

            // Transfer In
            $stmt_in = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan, id_user, id_gudang_asal, id_gudang_tujuan) VALUES (?, datetime('now', 'localtime'), 'TRANSFER_IN', ?, ?, ?, ?, ?)");
            $stmt_in->execute([$id_furniture, $qty, $keterangan, $id_user, $id_gudang_asal, $id_gudang_tujuan]);

            $pdo->commit();
            $success = "Transfer antar gudang berhasil dicatat!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$gudangs = $pdo->query("SELECT * FROM tb_gudang ORDER BY nama_gudang ASC")->fetchAll();
$furnitures = $pdo->query("SELECT id_furniture, kode_barang, nama_barang, stok_tersedia FROM tb_furniture WHERE stok_tersedia > 0 ORDER BY nama_barang ASC")->fetchAll();

$transfers = $pdo->query("
    SELECT m.*, f.kode_barang, f.nama_barang, u.nama_lengkap,
           ga.nama_gudang as gudang_asal, gt.nama_gudang as gudang_tujuan
    FROM tb_mutasi_stok m
    JOIN tb_furniture f ON m.id_furniture = f.id_furniture
    JOIN tb_users u ON m.id_user = u.id_user
    LEFT JOIN tb_gudang ga ON m.id_gudang_asal = ga.id_gudang
    LEFT JOIN tb_gudang gt ON m.id_gudang_tujuan = gt.id_gudang
    WHERE m.jenis_mutasi = 'TRANSFER_OUT' OR m.jenis_mutasi = 'TRANSFER_IN'
    ORDER BY m.tgl_mutasi DESC
")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Transfer Antar Gudang</h2>
            <p class="text-slate-500 font-medium mt-1">Catat dan pantau mutasi barang antar lokasi gudang.</p>
        </div>
        <button onclick="document.getElementById('mAdd').classList.remove('hidden')" class="bg-amber-500 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all">Buat Transfer</button>
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
                    <th class="px-8 py-6">Waktu</th>
                    <th class="px-8 py-6">Barang</th>
                    <th class="px-8 py-6">Jenis Mutasi</th>
                    <th class="px-8 py-6">Gudang Asal &rarr; Tujuan</th>
                    <th class="px-8 py-6">Qty</th>
                    <th class="px-8 py-6">Keterangan</th>
                    <th class="px-8 py-6">User</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach($transfers as $t): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-8 py-6 text-xs font-bold text-slate-500"><?= date('d M Y H:i', strtotime($t['tgl_mutasi'])) ?></td>
                    <td class="px-8 py-6">
                        <p class="font-bold text-navy-900"><?= htmlspecialchars($t['nama_barang']) ?></p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><?= htmlspecialchars($t['kode_barang']) ?></p>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full <?= $t['jenis_mutasi'] == 'TRANSFER_IN' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' ?>">
                            <?= $t['jenis_mutasi'] ?>
                        </span>
                    </td>
                    <td class="px-8 py-6 text-xs text-slate-500">
                        <span class="font-bold"><?= htmlspecialchars($t['gudang_asal'] ?? '-') ?></span> &rarr; <span class="font-bold"><?= htmlspecialchars($t['gudang_tujuan'] ?? '-') ?></span>
                    </td>
                    <td class="px-8 py-6 font-black <?= $t['qty'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $t['qty'] > 0 ? '+'.$t['qty'] : $t['qty'] ?>
                    </td>
                    <td class="px-8 py-6 text-slate-400 italic text-xs"><?= htmlspecialchars($t['keterangan']) ?></td>
                    <td class="px-8 py-6 text-xs font-bold text-slate-500"><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Transfer -->
<div id="mAdd" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg mx-4 overflow-hidden animate-fade-in">
        <div class="px-10 py-8 border-b border-slate-50 bg-amber-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-amber-900">Buat Transfer Gudang</h3>
            <button onclick="closeModal()" class="text-amber-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Pilih Barang</label>
                <select name="id_furniture" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="" disabled selected>Pilih barang...</option>
                    <?php foreach($furnitures as $f): ?>
                        <option value="<?= $f['id_furniture'] ?>"><?= $f['kode_barang'] ?> - <?= $f['nama_barang'] ?> (Sisa: <?= $f['stok_tersedia'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Gudang Asal</label>
                    <select name="id_gudang_asal" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="" disabled selected>Pilih...</option>
                        <?php foreach($gudangs as $g): ?>
                            <option value="<?= $g['id_gudang'] ?>"><?= htmlspecialchars($g['nama_gudang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Gudang Tujuan</label>
                    <select name="id_gudang_tujuan" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="" disabled selected>Pilih...</option>
                        <?php foreach($gudangs as $g): ?>
                            <option value="<?= $g['id_gudang'] ?>"><?= htmlspecialchars($g['nama_gudang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Qty Transfer</label>
                <input type="number" name="qty" min="1" required placeholder="Jumlah barang" class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Keterangan</label>
                <textarea name="keterangan" rows="2" required placeholder="Alasan transfer..." class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500"></textarea>
            </div>

            <button type="submit" class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Proses Transfer</button>
        </form>
    </div>
</div>

<script>
    function closeModal() { document.getElementById('mAdd').classList.add('hidden'); }
    
    // Validasi Gudang asal dan tujuan tidak sama
    document.querySelector('form').addEventListener('submit', function(e) {
        let asal = document.querySelector('select[name="id_gudang_asal"]').value;
        let tujuan = document.querySelector('select[name="id_gudang_tujuan"]').value;
        if (asal === tujuan && asal !== "") {
            e.preventDefault();
            alert("Gudang Tujuan tidak boleh sama dengan Gudang Asal!");
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
