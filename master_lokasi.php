<?php
require 'config.php';
require_access(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $blok = $_POST['nama_blok'];
        $rak = $_POST['rak'];
        $deskripsi = $_POST['deskripsi'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO tb_lokasi (nama_blok, rak, deskripsi) VALUES (?, ?, ?)");
            $stmt->execute([$blok, $rak, $deskripsi]);
            $success = "Lokasi berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error = "Gagal menambah lokasi: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT * FROM tb_lokasi ORDER BY id_lokasi DESC");
$lokasi = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-center mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Master Lokasi Gudang</h2>
            <p class="text-slate-500 font-medium mt-1">Manajemen blok & rak penyimpanan barang.</p>
        </div>
        <button onclick="document.getElementById('modalAdd').classList.remove('hidden')" class="bg-navy-900 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-navy-900/10 hover:bg-navy-800 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Lokasi
        </button>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="font-bold text-sm"><?= $success ?></span>
        </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3">
            <span class="font-bold text-sm"><?= $error ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">ID</th>
                        <th class="px-8 py-6">Nama Blok</th>
                        <th class="px-8 py-6">Kode Rak</th>
                        <th class="px-8 py-6">Deskripsi</th>
                        <th class="px-8 py-6 text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($lokasi as $l): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <span class="font-black text-slate-400">#<?= $l['id_lokasi'] ?></span>
                        </td>
                        <td class="px-8 py-6">
                            <span class="font-black text-navy-900 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($l['nama_blok']) ?></span>
                        </td>
                        <td class="px-8 py-6">
                            <p class="font-bold text-slate-700"><?= htmlspecialchars($l['rak']) ?></p>
                        </td>
                        <td class="px-8 py-6 text-slate-500 text-xs w-1/3">
                            <?= htmlspecialchars($l['deskripsi']) ?>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-navy-900 hover:text-white transition-all flex items-center justify-center mx-auto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add -->
<div id="modalAdd" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg mx-4 overflow-hidden animate-fade-in">
        <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
            <div>
                <h3 class="font-black text-2xl text-navy-900 tracking-tight">Lokasi Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Tambah area penyimpanan.</p>
            </div>
            <button onclick="document.getElementById('modalAdd').classList.add('hidden')" class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 hover:text-navy-900 transition-all">&times;</button>
        </div>
        <form method="POST" action="master_lokasi.php" class="p-10">
            <input type="hidden" name="action" value="add">
            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Nama Blok</label>
                    <input type="text" name="nama_blok" required placeholder="Blok A" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-amber-500 transition-all outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Kode Rak</label>
                    <input type="text" name="rak" required placeholder="Rak 01" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-amber-500 transition-all outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Deskripsi / Peruntukan</label>
                    <textarea name="deskripsi" rows="2" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-amber-500 transition-all outline-none"></textarea>
                </div>
            </div>
            <div class="flex gap-4 mt-10">
                <button type="button" onclick="document.getElementById('modalAdd').classList.add('hidden')" class="flex-1 py-4 rounded-2xl border border-slate-200 text-slate-500 font-bold text-sm hover:bg-slate-50 transition-all">Batal</button>
                <button type="submit" class="flex-1 py-4 rounded-2xl bg-navy-900 text-white font-bold text-sm shadow-xl shadow-navy-900/20 hover:bg-navy-800 transition-all">Simpan Lokasi</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
