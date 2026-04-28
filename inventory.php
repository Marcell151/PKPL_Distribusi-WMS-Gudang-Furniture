<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'opname') {
        require_access(['Admin', 'Supervisor', 'Staff Gudang']);
        $id_f = $_POST['id_furniture'];
        $qty_fisik = (int)$_POST['qty_fisik_opname'];
        $keterangan = $_POST['keterangan_opname'];
        try {
            $stmt = $pdo->prepare("SELECT stok_tersedia FROM tb_furniture WHERE id_furniture = ?");
            $stmt->execute([$id_f]);
            $stok_sistem = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("INSERT INTO tb_opname (tgl_request, id_furniture, qty_sistem, qty_fisik, alasan, status, id_user_request) VALUES (datetime('now', 'localtime'), ?, ?, ?, ?, 'Pending Approval', ?)");
            $stmt->execute([$id_f, $stok_sistem, $qty_fisik, $keterangan, $_SESSION['user']['id_user']]);
            $success_opname = "Request Opname berhasil diajukan dan menunggu Approval!";
        } catch (PDOException $e) { $error_opname = $e->getMessage(); }
    } elseif ($_POST['action'] === 'approve_opname') {
        require_access(['Admin', 'Supervisor']);
        $id_opname = $_POST['id_opname'];
        $status = $_POST['status_approval'];
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM tb_opname WHERE id_opname = ?");
            $stmt->execute([$id_opname]);
            $op = $stmt->fetch();
            if ($status === 'Approved' && $op['status'] === 'Pending Approval') {
                $selisih = $op['qty_fisik'] - $op['qty_sistem'];
                $stmt = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = ? WHERE id_furniture = ?");
                $stmt->execute([$op['qty_fisik'], $op['id_furniture']]);
                $stmt = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan) VALUES (?, datetime('now', 'localtime'), 'ADJUST_OPNAME', ?, ?)");
                $stmt->execute([$op['id_furniture'], $selisih, "Opname Disetujui: " . $op['alasan']]);
            }
            $stmt = $pdo->prepare("UPDATE tb_opname SET status = ?, id_user_approve = ? WHERE id_opname = ?");
            $stmt->execute([$status, $_SESSION['user']['id_user'], $id_opname]);
            $pdo->commit();
            $success_opname = "Opname berhasil di-" . $status . "!";
        } catch (Exception $e) { $pdo->rollBack(); $error_opname = $e->getMessage(); }
    } elseif ($_POST['action'] === 'mutasi_internal') {
        require_access(['Admin', 'Supervisor', 'Staff Gudang']);
        $id_f = $_POST['id_furniture'];
        $id_lokasi_baru = $_POST['id_lokasi_baru'];
        $keterangan = $_POST['keterangan_pindah'];
        try {
            $stmt = $pdo->prepare("UPDATE tb_furniture SET id_lokasi = ? WHERE id_furniture = ?");
            $stmt->execute([$id_lokasi_baru, $id_f]);
            $success_mutasi = "Barang berhasil dipindah lokasi!";
        } catch (Exception $e) { $error_mutasi = $e->getMessage(); }
    } elseif ($_POST['action'] === 'lapor_rusak') {
        require_access(['Admin', 'Staff Gudang']);
        $id_f = $_POST['id_furniture'];
        $qty_rusak = (int)$_POST['qty_rusak'];
        $keterangan = $_POST['keterangan_rusak'];
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT stok_tersedia FROM tb_furniture WHERE id_furniture = ?");
            $stmt->execute([$id_f]);
            $stok_sistem = $stmt->fetchColumn();
            if ($stok_sistem < $qty_rusak) {
                throw new Exception("Qty rusak melebihi stok tersedia!");
            }
            $stmt = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = stok_tersedia - ?, stok_karantina = stok_karantina + ? WHERE id_furniture = ?");
            $stmt->execute([$qty_rusak, $qty_rusak, $id_f]);
            $stmt = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan) VALUES (?, datetime('now', 'localtime'), 'MUTASI_RUSAK', ?, ?)");
            $stmt->execute([$id_f, -$qty_rusak, "Pindah Karantina: " . $keterangan]);
            $pdo->commit();
            $success_rusak = "Barang berhasil dipindah ke Stok Karantina!";
        } catch (Exception $e) { $pdo->rollBack(); $error_rusak = $e->getMessage(); }
    }
}

$stmt = $pdo->query("SELECT f.*, l.nama_blok, l.rak FROM tb_furniture f LEFT JOIN tb_lokasi l ON f.id_lokasi = l.id_lokasi ORDER BY f.nama_barang ASC");
$furniture_list = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM tb_lokasi ORDER BY nama_blok ASC");
$lokasi_list = $stmt->fetchAll();

$stmt = $pdo->query("SELECT o.*, f.kode_barang, f.nama_barang, u.nama_lengkap as pemohon FROM tb_opname o JOIN tb_furniture f ON o.id_furniture = f.id_furniture JOIN tb_users u ON o.id_user_request = u.id_user ORDER BY o.id_opname DESC LIMIT 50");
$opname_list = $stmt->fetchAll();

$where = ""; $params = [];
$is_filtered = false;
$current_stok = 0;
$filtered_furniture = null;

if (isset($_GET['filter_barang']) && !empty($_GET['filter_barang'])) {
    $where = "WHERE m.id_furniture = ?";
    $params[] = $_GET['filter_barang'];
    $is_filtered = true;
    
    foreach($furniture_list as $f) {
        if ($f['id_furniture'] == $_GET['filter_barang']) {
            $filtered_furniture = $f;
            $current_stok = $f['stok_tersedia'];
            break;
        }
    }
}

$stmt = $pdo->prepare("SELECT m.*, f.nama_barang, f.kode_barang FROM tb_mutasi_stok m JOIN tb_furniture f ON m.id_furniture = f.id_furniture $where ORDER BY m.id_mutasi DESC LIMIT 100");
$stmt->execute($params);
$mutasi_list = $stmt->fetchAll();

if ($is_filtered && count($mutasi_list) > 0) {
    $temp_sisa = $current_stok;
    foreach ($mutasi_list as $key => $m) {
        $mutasi_list[$key]['sisa'] = $temp_sisa;
        $temp_sisa = $temp_sisa - $m['qty'];
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in print:p-0">
    <header class="mb-10 print:hidden">
        <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Logistik & Inventory</h2>
        <p class="text-slate-500 font-medium mt-1">Audit mutasi stok dan rekonsiliasi fisik gudang.</p>
    </header>

    <div class="flex flex-wrap gap-2 mb-10 bg-slate-200 p-2 rounded-2xl w-fit print:hidden">
        <button id="btn-kartu" onclick="st('k')" class="px-6 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg">Kartu Stok</button>
        <?php if (check_access(['Admin', 'Supervisor', 'Staff Gudang'])): ?>
        <button id="btn-opname" onclick="st('o')" class="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50">Request Opname</button>
        <?php endif; ?>
        <?php if (check_access(['Admin', 'Supervisor'])): ?>
        <button id="btn-approval" onclick="st('a')" class="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50 relative">Approval Opname
            <?php $pending = count(array_filter($opname_list, fn($x) => $x['status'] == 'Pending Approval')); if($pending>0): ?>
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full animate-bounce"><?= $pending ?></span>
            <?php endif; ?>
        </button>
        <?php endif; ?>
        <?php if (check_access(['Admin', 'Supervisor', 'Staff Gudang'])): ?>
        <button id="btn-mutasi" onclick="st('m')" class="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50">Mutasi Internal</button>
        <button id="btn-rusak" onclick="st('r')" class="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50">Lapor Rusak</button>
        <?php endif; ?>
    </div>

    <?php if(isset($success_opname)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm print:hidden"><?= $success_opname ?></div>
    <?php endif; ?>
    <?php if(isset($error_opname)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm print:hidden"><?= $error_opname ?></div>
    <?php endif; ?>
    <?php if(isset($success_mutasi)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm print:hidden"><?= $success_mutasi ?></div>
    <?php endif; ?>
    <?php if(isset($error_mutasi)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm print:hidden"><?= $error_mutasi ?></div>
    <?php endif; ?>
    <?php if(isset($success_rusak)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm print:hidden"><?= $success_rusak ?></div>
    <?php endif; ?>
    <?php if(isset($error_rusak)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm print:hidden"><?= $error_rusak ?></div>
    <?php endif; ?>

    <div id="tk" class="space-y-6 print:space-y-0">
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 mb-8 flex flex-wrap gap-4 items-end print:hidden">
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
                <button type="submit" class="bg-navy-900 text-white px-8 py-4 rounded-2xl font-bold text-sm shadow-xl shadow-navy-900/10 hover:bg-navy-800 transition-all">Filter</button>
                <?php if ($is_filtered): ?>
                <button type="button" onclick="window.print()" class="bg-amber-500 text-white px-8 py-4 rounded-2xl font-bold text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak Kartu Stok
                </button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($is_filtered && $filtered_furniture): ?>
        <!-- Print Header -->
        <div class="hidden print:block mb-8 border-b-2 border-navy-900 pb-4 mt-8">
            <h1 class="text-3xl font-black text-navy-900 uppercase tracking-tighter mb-6 text-center">KARTU STOK GUDANG</h1>
            <div class="grid grid-cols-2 gap-4 text-sm text-black">
                <div>
                    <p><span class="font-bold w-32 inline-block">Nama Barang</span>: <?= htmlspecialchars($filtered_furniture['nama_barang']) ?></p>
                    <p><span class="font-bold w-32 inline-block">Kode Barang</span>: <?= htmlspecialchars($filtered_furniture['kode_barang']) ?></p>
                </div>
                <div class="text-right">
                    <p><span class="font-bold">Periode Cetak</span>: <?= date('d M Y H:i') ?></p>
                    <p><span class="font-bold">Area Blok</span>: <?= htmlspecialchars($filtered_furniture['nama_blok']) ?> - <?= htmlspecialchars($filtered_furniture['rak']) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden print:shadow-none print:border-none print:rounded-none">
            <div class="overflow-x-auto">
                <table class="w-full text-left print:border-collapse">
                    <thead>
                        <?php if ($is_filtered): ?>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50 print:border-slate-800 print:text-black">
                            <th class="px-8 py-6 print:px-2 print:py-3 print:border">Tanggal</th>
                            <th class="px-8 py-6 print:px-2 print:py-3 print:border">No. Bukti / Keterangan</th>
                            <th class="px-8 py-6 text-center print:px-2 print:py-3 print:border">Masuk</th>
                            <th class="px-8 py-6 text-center print:px-2 print:py-3 print:border">Keluar</th>
                            <th class="px-8 py-6 text-center print:px-2 print:py-3 print:border">Sisa</th>
                        </tr>
                        <?php else: ?>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                            <th class="px-8 py-6">Tanggal</th>
                            <th class="px-8 py-6">Barang</th>
                            <th class="px-8 py-6">Status</th>
                            <th class="px-8 py-6 text-center">Qty</th>
                            <th class="px-8 py-6">Memo</th>
                        </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        <?php foreach($mutasi_list as $m): ?>
                        <?php if ($is_filtered): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors print:text-black print:divide-y-0 print:border-b print:border-slate-200">
                            <td class="px-8 py-6 text-slate-500 font-medium print:px-2 print:py-3 print:border print:text-black"><?= date('d/m/Y H:i', strtotime($m['tgl_mutasi'])) ?></td>
                            <td class="px-8 py-6 print:px-2 print:py-3 print:border">
                                <span class="font-bold text-navy-900 print:text-black"><?= $m['jenis_mutasi'] ?></span><br>
                                <span class="text-xs text-slate-500 italic print:text-black"><?= htmlspecialchars($m['keterangan']) ?></span>
                            </td>
                            <td class="px-8 py-6 text-center font-bold text-blue-600 print:px-2 print:py-3 print:border print:text-black"><?= $m['qty'] > 0 ? $m['qty'] : '-' ?></td>
                            <td class="px-8 py-6 text-center font-bold text-red-600 print:px-2 print:py-3 print:border print:text-black"><?= $m['qty'] < 0 ? abs($m['qty']) : '-' ?></td>
                            <td class="px-8 py-6 text-center font-black text-lg text-navy-900 bg-slate-50/50 print:px-2 print:py-3 print:border print:bg-transparent print:text-black"><?= $m['sisa'] ?></td>
                        </tr>
                        <?php else: ?>
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
                        <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if(count($mutasi_list) === 0): ?>
                        <tr><td colspan="5" class="px-8 py-6 text-center text-slate-400 italic">Belum ada riwayat mutasi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (check_access(['Admin', 'Supervisor', 'Staff Gudang'])): ?>
    <div id="to" class="hidden animate-fade-in">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-12 max-w-2xl">
            <h3 class="text-2xl font-black text-navy-900 mb-8">Form Request Opname</h3>
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
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Qty Fisik (Aktual)</label>
                        <input type="number" name="qty_fisik_opname" required class="w-full bg-amber-50 border-none rounded-2xl p-5 text-xl font-black text-amber-600 focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Alasan Penyesuaian</label>
                    <textarea name="keterangan_opname" required rows="2" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-medium text-navy-900 focus:ring-2 focus:ring-amber-500 outline-none" placeholder="Alasan selisih stok..."></textarea>
                </div>
                <button type="submit" class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-2xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Kirim Request Opname</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (check_access(['Admin', 'Supervisor'])): ?>
    <div id="ta" class="hidden animate-fade-in">
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                            <th class="px-8 py-6">Tgl Request</th>
                            <th class="px-8 py-6">Barang</th>
                            <th class="px-8 py-6 text-center">Sistem -> Fisik</th>
                            <th class="px-8 py-6">Alasan</th>
                            <th class="px-8 py-6">Pemohon</th>
                            <th class="px-8 py-6 text-center">Status / Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        <?php foreach($opname_list as $o): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6 text-slate-500 text-xs"><?= date('d M Y, H:i', strtotime($o['tgl_request'])) ?></td>
                            <td class="px-8 py-6">
                                <p class="font-bold text-navy-900"><?= htmlspecialchars($o['kode_barang']) ?></p>
                                <p class="text-[10px] text-slate-400 truncate w-40"><?= htmlspecialchars($o['nama_barang']) ?></p>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="font-black text-slate-400"><?= $o['qty_sistem'] ?></span>
                                <span class="mx-2 text-slate-300">-></span>
                                <span class="font-black text-amber-600"><?= $o['qty_fisik'] ?></span>
                            </td>
                            <td class="px-8 py-6 text-slate-500 italic text-xs"><?= htmlspecialchars($o['alasan']) ?></td>
                            <td class="px-8 py-6 font-bold text-navy-900 text-xs"><?= htmlspecialchars($o['pemohon']) ?></td>
                            <td class="px-8 py-6 text-center">
                                <?php if($o['status'] === 'Pending Approval'): ?>
                                    <form method="POST" action="inventory.php" class="flex gap-2 justify-center">
                                        <input type="hidden" name="action" value="approve_opname">
                                        <input type="hidden" name="id_opname" value="<?= $o['id_opname'] ?>">
                                        <button type="submit" name="status_approval" value="Approved" class="px-4 py-2 bg-green-500 text-white rounded-lg text-xs font-bold hover:bg-green-600 shadow-lg shadow-green-500/20">Setujui</button>
                                        <button type="submit" name="status_approval" value="Rejected" class="px-4 py-2 bg-red-100 text-red-600 rounded-lg text-xs font-bold hover:bg-red-200">Tolak</button>
                                    </form>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?= $o['status'] == 'Approved' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>"><?= $o['status'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($opname_list) === 0): ?>
                        <tr><td colspan="6" class="px-8 py-6 text-center text-slate-400 italic">Belum ada data opname.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (check_access(['Admin', 'Supervisor', 'Staff Gudang'])): ?>
    <div id="tm" class="hidden animate-fade-in">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-12 max-w-2xl">
            <h3 class="text-2xl font-black text-navy-900 mb-8">Mutasi Internal (Pindah Rak)</h3>
            <form method="POST" action="inventory.php" class="space-y-8">
                <input type="hidden" name="action" value="mutasi_internal">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Produk yang Dipindah</label>
                    <select name="id_furniture" required class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="" disabled selected>-- Pilih Furniture --</option>
                        <?php foreach($furniture_list as $f): ?>
                            <option value="<?= $f['id_furniture'] ?>"><?= htmlspecialchars($f['kode_barang']) ?> - <?= htmlspecialchars($f['nama_barang']) ?> (Lok: <?= htmlspecialchars($f['nama_blok'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Lokasi / Rak Tujuan Baru</label>
                    <select name="id_lokasi_baru" required class="w-full bg-blue-50 border-none rounded-2xl p-5 text-sm font-bold text-blue-900 focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="" disabled selected>-- Pilih Lokasi Baru --</option>
                        <?php foreach($lokasi_list as $l): ?>
                            <option value="<?= $l['id_lokasi'] ?>"><?= htmlspecialchars($l['nama_blok']) ?> - <?= htmlspecialchars($l['rak']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Alasan Pindah</label>
                    <textarea name="keterangan_pindah" required rows="2" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-medium text-navy-900 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Contoh: Rak penuh, optimalisasi lorong..."></textarea>
                </div>
                <button type="submit" class="w-full py-5 rounded-2xl bg-blue-600 text-white font-black text-sm shadow-2xl shadow-blue-600/20 hover:bg-blue-700 transition-all uppercase tracking-widest">Pindah Lokasi</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (check_access(['Admin', 'Staff Gudang'])): ?>
    <div id="tr" class="hidden animate-fade-in">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-12 max-w-2xl">
            <h3 class="text-2xl font-black text-red-600 mb-8">Form Lapor Rusak (Pindah Karantina)</h3>
            <form method="POST" action="inventory.php" class="space-y-8">
                <input type="hidden" name="action" value="lapor_rusak">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Produk Rusak</label>
                    <select name="id_furniture" id="idfr" required onchange="ur()" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-red-500 outline-none appearance-none">
                        <option value="" disabled selected>-- Pilih Furniture --</option>
                        <?php foreach($furniture_list as $f): ?>
                            <option value="<?= $f['id_furniture'] ?>" data-s="<?= $f['stok_tersedia'] ?>"><?= htmlspecialchars($f['kode_barang']) ?> - <?= htmlspecialchars($f['nama_barang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Stok Tersedia</label>
                        <input type="text" id="ssr" disabled class="w-full bg-slate-100 border-none rounded-2xl p-5 text-xl font-black text-slate-400" value="-">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Qty Rusak</label>
                        <input type="number" name="qty_rusak" min="1" required class="w-full bg-red-50 border-none rounded-2xl p-5 text-xl font-black text-red-600 focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Keterangan / Alasan</label>
                    <textarea name="keterangan_rusak" required rows="2" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-medium text-navy-900 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Deskripsi kerusakan..."></textarea>
                </div>
                <button type="submit" class="w-full py-5 rounded-2xl bg-red-500 text-white font-black text-sm shadow-2xl shadow-red-500/20 hover:bg-red-600 transition-all uppercase tracking-widest">Pindah ke Karantina</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function st(t){
        const tk=document.getElementById('tk'), to=document.getElementById('to'), tr=document.getElementById('tr'), ta=document.getElementById('ta'), tm=document.getElementById('tm');
        const bk=document.getElementById('btn-kartu'), bo=document.getElementById('btn-opname'), br=document.getElementById('btn-rusak'), ba=document.getElementById('btn-approval'), bm=document.getElementById('btn-mutasi');
        
        [bk,bo,br,ba,bm].forEach(b => { if(b) b.className="px-6 py-3 rounded-xl font-black text-sm transition-all text-slate-600 hover:bg-white/50 relative"; });
        [tk,to,tr,ta,tm].forEach(p => { if(p) p.classList.add('hidden'); });

        if(t==='k'){ 
            if(tk) tk.classList.remove('hidden'); if(bk) bk.className="px-6 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg"; 
        } else if(t==='o'){ 
            if(to) to.classList.remove('hidden'); if(bo) bo.className="px-6 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg"; 
        } else if(t==='r'){
            if(tr) tr.classList.remove('hidden'); if(br) br.className="px-6 py-3 rounded-xl font-black text-sm transition-all bg-red-600 text-white shadow-lg shadow-red-600/30"; 
        } else if(t==='a'){
            if(ta) ta.classList.remove('hidden'); if(ba) ba.className="px-6 py-3 rounded-xl font-black text-sm transition-all bg-navy-900 text-white shadow-lg relative"; 
        } else if(t==='m'){
            if(tm) tm.classList.remove('hidden'); if(bm) bm.className="px-6 py-3 rounded-xl font-black text-sm transition-all bg-blue-600 text-white shadow-lg shadow-blue-600/30"; 
        }
    }
    function u(){ const s=document.getElementById('idfo'), d=document.getElementById('ssd'), o=s.options[s.selectedIndex]; d.value=o?o.getAttribute('data-s'):'-'; }
    function ur(){ const s=document.getElementById('idfr'), d=document.getElementById('ssr'), o=s.options[s.selectedIndex]; d.value=o?o.getAttribute('data-s'):'-'; }
</script>

<style>
    @media print {
        @page { size: portrait; margin: 1cm; }
        aside, header, nav, #btn-kartu, #btn-opname, .print\:hidden { display: none !important; }
        main { height: auto !important; overflow: visible !important; width: 100% !important; background: white !important; }
        .flex-1 { overflow: visible !important; }
        body { background: white !important; }
        .animate-fade-in { transform: none !important; animation: none !important; }
        
        /* Ensure table prints nicely */
        table { border-collapse: collapse !important; width: 100% !important; }
        th, td { border: 1px solid #cbd5e1 !important; color: #000 !important; }
    }
</style>

<?php include 'includes/footer.php'; ?>
