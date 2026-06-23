<?php
require 'config.php';
require_access(['Admin', 'Supervisor', 'Staff Gudang']);

$stmt = $pdo->query("SELECT f.*, l.nama_blok, l.rak FROM tb_furniture f LEFT JOIN tb_lokasi l ON f.id_lokasi = l.id_lokasi ORDER BY f.nama_barang ASC");
$furniture = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-center mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Informasi Stok</h2>
            <p class="text-slate-500 font-medium mt-1">Pantau ketersediaan barang jadi dan status karantina.</p>
        </div>
    </header>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">SKU / Kode</th>
                        <th class="px-8 py-6">Nama Furniture</th>
                        <th class="px-8 py-6">Lokasi / Blok</th>
                        <th class="px-8 py-6 text-center">Ready</th>
                        <th class="px-8 py-6 text-center">Karantina</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($furniture as $f): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <span class="font-black text-navy-900 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($f['kode_barang']) ?></span>
                        </td>
                        <td class="px-8 py-6">
                            <p class="font-bold text-slate-700"><?= htmlspecialchars($f['nama_barang']) ?></p>
                        </td>
                        <td class="px-8 py-6">
                            <span class="bg-slate-100 text-slate-500 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest"><?= htmlspecialchars($f['nama_blok'] ?? 'N/A') ?> - <?= htmlspecialchars($f['rak'] ?? 'N/A') ?></span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span class="text-lg font-black text-navy-900"><?= $f['stok_tersedia'] ?></span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span class="text-lg font-black <?= $f['stok_karantina'] > 0 ? 'text-red-500' : 'text-slate-300' ?>"><?= $f['stok_karantina'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($furniture) === 0): ?>
                    <tr><td colspan="5" class="px-8 py-6 text-center text-slate-400 italic">Belum ada data barang.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
