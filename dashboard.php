<?php
require 'config.php';

// Fetch stats
$stmt = $pdo->query("SELECT COUNT(*) as pending FROM tb_sales_order WHERE status = 'Pending'");
$so_pending = $stmt->fetch()['pending'];

$stmt = $pdo->query("SELECT SUM(stok_tersedia) as total_stok FROM tb_furniture");
$total_stok = $stmt->fetch()['total_stok'] ?? 0;

$stmt = $pdo->query("SELECT SUM(stok_karantina) as total_karantina FROM tb_furniture");
$total_karantina = $stmt->fetch()['total_karantina'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total_items FROM tb_furniture");
$total_items = $stmt->fetch()['total_items'] ?? 0;

// Fetch last 10 mutations for a fuller dashboard
$stmt = $pdo->query("
    SELECT m.*, f.nama_barang, f.kode_barang 
    FROM tb_mutasi_stok m
    JOIN tb_furniture f ON m.id_furniture = f.id_furniture
    ORDER BY m.id_mutasi DESC LIMIT 10
");
$recent_mutations = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <!-- Welcome Section -->
    <div class="flex justify-between items-center mb-10">
        <div>
            <h3 class="text-3xl font-extrabold text-navy-900 tracking-tight">Overview Gudang</h3>
            <p class="text-slate-500 font-medium">Selamat bekerja, <span class="text-navy-900 font-bold"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?></span>. Pantau stok furniture Anda secara real-time.</p>
        </div>
        <div class="flex gap-3">
            <a href="inbound.php" class="bg-white border border-slate-200 text-navy-900 px-5 py-3 rounded-xl font-bold text-sm shadow-sm hover:bg-slate-50 transition-all">Input Inbound</a>
            <a href="outbound.php" class="bg-navy-900 text-white px-5 py-3 rounded-xl font-bold text-sm shadow-lg shadow-navy-900/20 hover:bg-navy-800 transition-all">Proses Outbound</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- Card 1 -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 card-hover">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-1 rounded-full uppercase tracking-wider">Antrean</span>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">SO Pending</p>
            <h4 class="text-3xl font-black text-navy-900"><?= $so_pending ?> <span class="text-xs font-medium text-slate-400">Order</span></h4>
        </div>

        <!-- Card 2 -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 card-hover">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <span class="text-[10px] font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded-full uppercase tracking-wider">Ready</span>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Total Stok Tersedia</p>
            <h4 class="text-3xl font-black text-navy-900"><?= number_format($total_stok) ?> <span class="text-xs font-medium text-slate-400">Unit</span></h4>
        </div>

        <!-- Card 3 -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 card-hover">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <span class="text-[10px] font-bold bg-red-100 text-red-700 px-2 py-1 rounded-full uppercase tracking-wider">Karantina</span>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Barang Rusak</p>
            <h4 class="text-3xl font-black text-navy-900"><?= $total_karantina ?> <span class="text-xs font-medium text-slate-400">Unit</span></h4>
        </div>

        <!-- Card 4 -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 card-hover">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                </div>
                <span class="text-[10px] font-bold bg-slate-100 text-slate-700 px-2 py-1 rounded-full uppercase tracking-wider">Master</span>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Varian Furniture</p>
            <h4 class="text-3xl font-black text-navy-900"><?= $total_items ?> <span class="text-xs font-medium text-slate-400">SKU</span></h4>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Mutations Table -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center">
                    <h4 class="text-lg font-extrabold text-navy-900">Riwayat Mutasi Terkini</h4>
                    <a href="inventory.php" class="text-blue-600 text-xs font-bold hover:underline">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50">
                                <th class="px-8 py-4">Waktu</th>
                                <th class="px-8 py-4">Barang</th>
                                <th class="px-8 py-4">Jenis</th>
                                <th class="px-8 py-4 text-center">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($recent_mutations as $m): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-4">
                                    <p class="text-xs font-bold text-navy-900"><?= date('H:i', strtotime($m['tgl_mutasi'])) ?></p>
                                    <p class="text-[10px] text-slate-400"><?= date('d M Y', strtotime($m['tgl_mutasi'])) ?></p>
                                </td>
                                <td class="px-8 py-4">
                                    <p class="text-xs font-bold text-navy-900"><?= htmlspecialchars($m['kode_barang']) ?></p>
                                    <p class="text-[10px] text-slate-500 truncate w-32"><?= htmlspecialchars($m['nama_barang']) ?></p>
                                </td>
                                <td class="px-8 py-4">
                                    <?php 
                                        $label = ''; $color = '';
                                        switch($m['jenis_mutasi']) {
                                            case 'IN': $label = 'Masuk'; $color = 'text-blue-600 bg-blue-50'; break;
                                            case 'OUT': $label = 'Keluar'; $color = 'text-green-600 bg-green-50'; break;
                                            case 'MUTASI_RUSAK': $label = 'Rusak'; $color = 'text-red-600 bg-red-50'; break;
                                            case 'ADJUST_OPNAME': $label = 'Opname'; $color = 'text-amber-600 bg-amber-50'; break;
                                        }
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter <?= $color ?>"><?= $label ?></span>
                                </td>
                                <td class="px-8 py-4 text-center">
                                    <span class="text-sm font-black <?= $m['qty'] > 0 ? 'text-blue-600' : 'text-red-600' ?>">
                                        <?= ($m['qty'] > 0 ? '+' : '') . $m['qty'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions / Quick Info -->
        <div class="space-y-6">
            <!-- Documentation Card -->
            <div class="bg-gradient-to-br from-navy-900 to-blue-900 rounded-[2rem] p-8 text-white shadow-xl shadow-navy-900/10 relative overflow-hidden group">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-all"></div>
                <h4 class="text-xl font-bold mb-2">Butuh Bantuan?</h4>
                <p class="text-blue-200 text-xs mb-6 leading-relaxed">Pelajari Use Case dan skema arsitektur sistem WMS-Furni di halaman dokumentasi resmi.</p>
                <a href="documentation.php" class="inline-block bg-amber-500 text-navy-900 px-6 py-3 rounded-xl font-bold text-xs hover:bg-amber-400 transition-all">Buka Dokumentasi</a>
            </div>

            <!-- Stock Health Placeholder -->
            <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100">
                <h4 class="text-sm font-bold text-navy-900 mb-6 uppercase tracking-widest">Kesehatan Stok</h4>
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between text-xs font-bold mb-2">
                            <span class="text-slate-500">Stok Siap Jual</span>
                            <span class="text-navy-900"><?= round(($total_stok / ($total_stok + $total_karantina + 1)) * 100) ?>%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full" style="width: <?= ($total_stok / ($total_stok + $total_karantina + 1)) * 100 ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs font-bold mb-2">
                            <span class="text-slate-500">Stok Karantina</span>
                            <span class="text-red-500"><?= round(($total_karantina / ($total_stok + $total_karantina + 1)) * 100) ?>%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-red-500 rounded-full" style="width: <?= ($total_karantina / ($total_stok + $total_karantina + 1)) * 100 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
