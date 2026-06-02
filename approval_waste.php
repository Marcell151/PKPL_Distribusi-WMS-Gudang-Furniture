<?php
require 'config.php';
require_access(['Admin', 'Supervisor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_action'])) {
    $id_waste = (int)$_POST['id_waste'];
    $status_action = $_POST['status_action']; // 'Approved' or 'Rejected'
    $approver_id = $_SESSION['user']['id_user'];

    try {
        $pdo->beginTransaction();

        // Ambil data laporan waste insidentil
        $stmt_waste = $pdo->prepare("SELECT w.*, f.nama_barang FROM tb_waste_insidentil w JOIN tb_furniture f ON w.id_furniture = f.id_furniture WHERE w.id_waste = ?");
        $stmt_waste->execute([$id_waste]);
        $report = $stmt_waste->fetch();

        if (!$report) {
            throw new Exception("Laporan kerusakan tidak ditemukan.");
        }

        if ($report['status'] !== 'Menunggu Approval') {
            throw new Exception("Laporan ini sudah diproses sebelumnya (Status: " . $report['status'] . ").");
        }

        if ($status_action === 'Approved') {
            // Validasi stok furniture saat ini
            $stmt_furn = $pdo->prepare("SELECT stok_tersedia FROM tb_furniture WHERE id_furniture = ?");
            $stmt_furn->execute([$report['id_furniture']]);
            $stok_tersedia = $stmt_furn->fetchColumn();

            if ($stok_tersedia < $report['qty_rusak']) {
                throw new Exception("Stok tersedia untuk '" . $report['nama_barang'] . "' saat ini (" . $stok_tersedia . ") tidak mencukupi untuk dikurangi sebanyak " . $report['qty_rusak'] . " unit.");
            }

            // 1. Kurangi stok_tersedia dan tambah stok_karantina
            $stmt_update_stok = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = stok_tersedia - ?, stok_karantina = stok_karantina + ? WHERE id_furniture = ?");
            $stmt_update_stok->execute([$report['qty_rusak'], $report['qty_rusak'], $report['id_furniture']]);

            // 2. Catat ke tb_mutasi_stok
            $stmt_mutasi = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan, id_user) VALUES (?, datetime('now', 'localtime'), 'MUTASI_RUSAK', ?, ?, ?)");
            $stmt_mutasi->execute([
                $report['id_furniture'], 
                -$report['qty_rusak'], 
                "Waste Insidentil (Approved). Kronologi: " . $report['keterangan'], 
                $approver_id
            ]);

            // 3. Ubah status laporan di tb_waste_insidentil
            $stmt_update_report = $pdo->prepare("UPDATE tb_waste_insidentil SET status = 'Approved', id_user_approver = ? WHERE id_waste = ?");
            $stmt_update_report->execute([$approver_id, $id_waste]);

            $success = "Laporan kerusakan #" . $id_waste . " berhasil DISETUJUI. Stok telah disesuaikan dan dipindahkan ke Karantina.";
        } elseif ($status_action === 'Rejected') {
            // Ubah status laporan di tb_waste_insidentil
            $stmt_update_report = $pdo->prepare("UPDATE tb_waste_insidentil SET status = 'Rejected', id_user_approver = ? WHERE id_waste = ?");
            $stmt_update_report->execute([$approver_id, $id_waste]);

            $success = "Laporan kerusakan #" . $id_waste . " telah DITOLAK. Tidak ada perubahan stok.";
        } else {
            throw new Exception("Aksi tidak valid.");
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch pending approval reports
$pending_reports = $pdo->query("
    SELECT w.*, f.kode_barang, f.nama_barang, f.stok_tersedia, u.nama_lengkap as pelapor
    FROM tb_waste_insidentil w
    JOIN tb_furniture f ON w.id_furniture = f.id_furniture
    LEFT JOIN tb_users u ON w.id_user_pelapor = u.id_user
    WHERE w.status = 'Menunggu Approval'
    ORDER BY w.id_waste ASC
")->fetchAll();

// Fetch processed reports history
$processed_reports = $pdo->query("
    SELECT w.*, f.kode_barang, f.nama_barang, u.nama_lengkap as pelapor, a.nama_lengkap as approver
    FROM tb_waste_insidentil w
    JOIN tb_furniture f ON w.id_furniture = f.id_furniture
    LEFT JOIN tb_users u ON w.id_user_pelapor = u.id_user
    LEFT JOIN tb_users a ON w.id_user_approver = a.id_user
    WHERE w.status != 'Menunggu Approval'
    ORDER BY w.id_waste DESC
    LIMIT 20
")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="mb-10">
        <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Persetujuan Laporan Kerusakan & Waste</h2>
        <p class="text-slate-500 font-medium mt-1">Otorisasi laporan kerusakan barang insidentil untuk mengurangi stok aktif gudang dan memindahkannya ke Karantina.</p>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $error ?></div>
    <?php endif; ?>

    <!-- PENDING APPROVAL SECTION -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden mb-12">
        <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center bg-amber-50/50">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 bg-amber-500 rounded-full animate-ping"></span>
                <h4 class="text-lg font-extrabold text-navy-900">Antrean Menunggu Persetujuan</h4>
            </div>
            <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-black"><?= count($pending_reports) ?> Pending</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">Tanggal Lapor</th>
                        <th class="px-8 py-6">Barang & SKU</th>
                        <th class="px-8 py-6 text-center">Qty Dilaporkan</th>
                        <th class="px-8 py-6">Kronologi Kerusakan</th>
                        <th class="px-8 py-6">Pelapor</th>
                        <th class="px-8 py-6 text-center">Aksi Keputusan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($pending_reports as $r): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6 text-slate-500 font-medium">
                            <?= date('d/m/Y', strtotime($r['tanggal_lapor'])) ?><br>
                            <span class="text-[10px] text-slate-400"><?= date('H:i', strtotime($r['tanggal_lapor'])) ?></span>
                        </td>
                        <td class="px-8 py-6">
                            <p class="font-black text-navy-900"><?= htmlspecialchars($r['kode_barang']) ?></p>
                            <p class="text-[10px] text-slate-500 truncate w-48"><?= htmlspecialchars($r['nama_barang']) ?></p>
                            <p class="text-[9px] font-bold text-slate-400">Stok Aktif: <?= $r['stok_tersedia'] ?> Unit</p>
                        </td>
                        <td class="px-8 py-6 text-center font-black text-lg text-amber-600"><?= $r['qty_rusak'] ?> Unit</td>
                        <td class="px-8 py-6 text-slate-600 font-medium max-w-xs break-words"><?= htmlspecialchars($r['keterangan']) ?></td>
                        <td class="px-8 py-6 font-bold text-navy-900 text-xs"><?= htmlspecialchars($r['pelapor'] ?? 'Sistem') ?></td>
                        <td class="px-8 py-6 text-center">
                            <form method="POST" class="flex gap-2 justify-center" onsubmit="return confirm('Apakah Anda yakin dengan keputusan ini?');">
                                <input type="hidden" name="id_waste" value="<?= $r['id_waste'] ?>">
                                <button type="submit" name="status_action" value="Approved" class="px-4 py-2.5 bg-green-500 text-white rounded-xl text-xs font-bold hover:bg-green-600 shadow-lg shadow-green-500/20 transition-all">
                                    Approve
                                </button>
                                <button type="submit" name="status_action" value="Rejected" class="px-4 py-2.5 bg-red-100 text-red-600 rounded-xl text-xs font-bold hover:bg-red-200 transition-all">
                                    Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($pending_reports)): ?>
                    <tr>
                        <td colspan="6" class="px-8 py-12 text-center text-slate-400 italic font-medium">Bagus! Tidak ada laporan waste yang sedang menunggu persetujuan.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- HISTORY / PROCESSED SECTION -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50">
            <h4 class="text-lg font-extrabold text-navy-900">Riwayat Keputusan Waste (Terakhir)</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">Tanggal</th>
                        <th class="px-8 py-6">Barang</th>
                        <th class="px-8 py-6 text-center">Qty Rusak</th>
                        <th class="px-8 py-6">Kronologi</th>
                        <th class="px-8 py-6">Pelapor / Approver</th>
                        <th class="px-8 py-6 text-center">Status Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($processed_reports as $r): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6 text-slate-500 font-medium">
                            <?= date('d/m/Y', strtotime($r['tanggal_lapor'])) ?><br>
                            <span class="text-[10px] text-slate-400"><?= date('H:i', strtotime($r['tanggal_lapor'])) ?></span>
                        </td>
                        <td class="px-8 py-6">
                            <p class="font-black text-navy-900"><?= htmlspecialchars($r['kode_barang']) ?></p>
                            <p class="text-[10px] text-slate-500 truncate w-48"><?= htmlspecialchars($r['nama_barang']) ?></p>
                        </td>
                        <td class="px-8 py-6 text-center font-bold text-slate-700"><?= $r['qty_rusak'] ?> Unit</td>
                        <td class="px-8 py-6 text-slate-500 italic max-w-xs truncate" title="<?= htmlspecialchars($r['keterangan']) ?>"><?= htmlspecialchars($r['keterangan']) ?></td>
                        <td class="px-8 py-6 font-bold text-navy-900 text-xs">
                            Pelapor: <span class="text-slate-600 font-medium"><?= htmlspecialchars($r['pelapor'] ?? 'Sistem') ?></span><br>
                            Approver: <span class="text-slate-600 font-medium"><?= htmlspecialchars($r['approver'] ?? '-') ?></span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <?php 
                                $status_class = 'bg-slate-100 text-slate-700';
                                if($r['status'] == 'Approved') $status_class = 'bg-green-100 text-green-700';
                                elseif($r['status'] == 'Rejected') $status_class = 'bg-red-100 text-red-700';
                            ?>
                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $status_class ?>"><?= htmlspecialchars($r['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($processed_reports)): ?>
                    <tr>
                        <td colspan="6" class="px-8 py-10 text-center text-slate-400 italic">Belum ada keputusan laporan waste yang tercatat.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
