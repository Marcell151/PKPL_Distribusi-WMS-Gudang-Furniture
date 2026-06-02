<?php
require 'config.php';
require_access(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lapor_waste') {
    $id_furniture = $_POST['id_furniture'];
    $qty_rusak = (int)$_POST['qty_rusak'];
    $keterangan = trim($_POST['keterangan']);
    $id_user_pelapor = $_SESSION['user']['id_user'];

    try {
        if ($qty_rusak <= 0) {
            throw new Exception("Jumlah barang rusak harus lebih besar dari 0.");
        }
        if (empty($keterangan)) {
            throw new Exception("Keterangan/kronologi kerusakan wajib diisi.");
        }

        // Cek stok tersedia untuk validasi awal
        $stmt_check = $pdo->prepare("SELECT stok_tersedia, nama_barang FROM tb_furniture WHERE id_furniture = ?");
        $stmt_check->execute([$id_furniture]);
        $furn = $stmt_check->fetch();

        if (!$furn) {
            throw new Exception("Furniture tidak ditemukan.");
        }

        if ($furn['stok_tersedia'] < $qty_rusak) {
            throw new Exception("Jumlah yang dilaporkan rusak (" . $qty_rusak . ") melebihi stok tersedia untuk '" . $furn['nama_barang'] . "' (Tersedia: " . $furn['stok_tersedia'] . ").");
        }

        // Simpan laporan ke database (Status: Menunggu Approval, Stok Belum Berubah)
        $stmt_insert = $pdo->prepare("INSERT INTO tb_waste_insidentil (id_furniture, qty_rusak, keterangan, tanggal_lapor, id_user_pelapor, status) VALUES (?, ?, ?, datetime('now', 'localtime'), ?, 'Menunggu Approval')");
        $stmt_insert->execute([$id_furniture, $qty_rusak, $keterangan, $id_user_pelapor]);

        $success = "Laporan kerusakan berhasil dikirim! Menunggu approval dari Supervisor/Admin.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch furniture list for select dropdown
$furniture_list = $pdo->query("SELECT * FROM tb_furniture WHERE stok_tersedia > 0 ORDER BY nama_barang ASC")->fetchAll();

// Fetch reporting history
$reports = $pdo->query("
    SELECT w.*, f.kode_barang, f.nama_barang, u.nama_lengkap as pelapor, a.nama_lengkap as approver
    FROM tb_waste_insidentil w
    JOIN tb_furniture f ON w.id_furniture = f.id_furniture
    LEFT JOIN tb_users u ON w.id_user_pelapor = u.id_user
    LEFT JOIN tb_users a ON w.id_user_approver = a.id_user
    ORDER BY w.id_waste DESC
")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Pelaporan Waste & Barang Rusak Insidentil</h2>
            <p class="text-slate-500 font-medium mt-1">Formulir bagi Staff Gudang untuk melaporkan barang rusak secara insidentil di area penyimpanan.</p>
        </div>
        <button onclick="document.getElementById('mReport').classList.remove('hidden')" class="bg-amber-500 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Lapor Barang Rusak
        </button>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $error ?></div>
    <?php endif; ?>

    <!-- History Table -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50">
            <h4 class="text-lg font-extrabold text-navy-900">Riwayat Laporan Kerusakan Insidentil</h4>
        </div>
        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                    <th class="px-8 py-6">Tanggal Lapor</th>
                    <th class="px-8 py-6">Barang</th>
                    <th class="px-8 py-6 text-center">Qty Cacat</th>
                    <th class="px-8 py-6">Kronologi / Keterangan</th>
                    <th class="px-8 py-6">Pelapor</th>
                    <th class="px-8 py-6 text-center">Status</th>
                    <th class="px-8 py-6">Approver</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach($reports as $r): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-8 py-6 text-slate-500 font-medium">
                        <?= date('d/m/Y', strtotime($r['tanggal_lapor'])) ?><br>
                        <span class="text-[10px] text-slate-400"><?= date('H:i', strtotime($r['tanggal_lapor'])) ?></span>
                    </td>
                    <td class="px-8 py-6">
                        <p class="font-black text-navy-900"><?= htmlspecialchars($r['kode_barang']) ?></p>
                        <p class="text-[10px] text-slate-500 truncate w-40"><?= htmlspecialchars($r['nama_barang']) ?></p>
                    </td>
                    <td class="px-8 py-6 text-center font-black text-lg text-red-600"><?= $r['qty_rusak'] ?> Unit</td>
                    <td class="px-8 py-6 text-slate-600 font-medium max-w-xs truncate" title="<?= htmlspecialchars($r['keterangan']) ?>"><?= htmlspecialchars($r['keterangan']) ?></td>
                    <td class="px-8 py-6 font-bold text-navy-900 text-xs"><?= htmlspecialchars($r['pelapor'] ?? 'Sistem') ?></td>
                    <td class="px-8 py-6 text-center">
                        <?php 
                            $status_class = 'bg-slate-100 text-slate-700';
                            if($r['status'] == 'Menunggu Approval') $status_class = 'bg-amber-100 text-amber-700';
                            elseif($r['status'] == 'Approved') $status_class = 'bg-green-100 text-green-700';
                            elseif($r['status'] == 'Rejected') $status_class = 'bg-red-100 text-red-700';
                        ?>
                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $status_class ?>"><?= htmlspecialchars($r['status']) ?></span>
                    </td>
                    <td class="px-8 py-6 font-bold text-slate-600 text-xs"><?= htmlspecialchars($r['approver'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="7" class="px-8 py-10 text-center text-slate-400 italic">Belum ada riwayat laporan kerusakan insidentil.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Lapor Waste -->
<div id="mReport" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50 overflow-y-auto py-10">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg mx-4 overflow-hidden animate-fade-in my-auto">
        <div class="px-10 py-8 border-b border-slate-50 bg-amber-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-amber-900">Laporkan Kerusakan Baru</h3>
            <button onclick="document.getElementById('mReport').classList.add('hidden')" class="text-amber-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <input type="hidden" name="action" value="lapor_waste">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Pilih Barang</label>
                <select name="id_furniture" id="lapor_id_furniture" required onchange="updateStockLabel()" class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none appearance-none cursor-pointer">
                    <option value="" disabled selected>-- Pilih Furniture --</option>
                    <?php foreach($furniture_list as $f): ?>
                        <option value="<?= $f['id_furniture'] ?>" data-stok="<?= $f['stok_tersedia'] ?>"><?= htmlspecialchars($f['kode_barang']) ?> - <?= htmlspecialchars($f['nama_barang']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Stok Tersedia (Sistem)</label>
                    <input type="text" id="lapor_stok_tersedia" disabled class="w-full bg-slate-100 rounded-2xl p-4 text-sm font-bold text-slate-400" value="-">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Jumlah Rusak (Qty)</label>
                    <input type="number" name="qty_rusak" id="lapor_qty_rusak" min="1" required class="w-full bg-red-50 text-red-600 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Keterangan / Alasan / Kronologi</label>
                <textarea name="keterangan" rows="3" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-semibold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500" placeholder="Contoh: Ketemu di Blok A, kaki kursi patah kesenggol forklift..."></textarea>
            </div>

            <button type="submit" class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Kirim Laporan Kerusakan</button>
        </form>
    </div>
</div>

<script>
function updateStockLabel() {
    const select = document.getElementById('lapor_id_furniture');
    const input = document.getElementById('lapor_stok_tersedia');
    const qtyInput = document.getElementById('lapor_qty_rusak');
    const option = select.options[select.selectedIndex];
    
    if (option && option.value !== "") {
        const stok = option.getAttribute('data-stok');
        input.value = stok + " Unit";
        qtyInput.max = stok;
    } else {
        input.value = "-";
        qtyInput.removeAttribute('max');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
