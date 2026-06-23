<?php
require 'config.php';
require_access(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $nama = $_POST['nama_gudang'];
        $jenis = $_POST['jenis'];
        $alamat = $_POST['alamat'];

        if ($_POST['action'] === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO tb_gudang (nama_gudang, jenis, alamat) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $jenis, $alamat]);
                $success = "Gudang baru berhasil ditambahkan!";
            } catch (PDOException $e) { $error = "Gagal menambah gudang: " . $e->getMessage(); }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id_gudang'];
            try {
                $stmt = $pdo->prepare("UPDATE tb_gudang SET nama_gudang = ?, jenis = ?, alamat = ? WHERE id_gudang = ?");
                $stmt->execute([$nama, $jenis, $alamat, $id]);
                $success = "Data gudang berhasil diperbarui!";
            } catch (PDOException $e) { $error = "Gagal memperbarui gudang: " . $e->getMessage(); }
        }
    }
}

if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tb_gudang WHERE id_gudang = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Gudang berhasil dihapus!";
    } catch (PDOException $e) { $error = "Gagal menghapus gudang (mungkin data masih digunakan)."; }
}

$gudangs = $pdo->query("SELECT * FROM tb_gudang ORDER BY nama_gudang ASC")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Master Gudang</h2>
            <p class="text-slate-500 font-medium mt-1">Kelola data gudang pusat dan cabang.</p>
        </div>
        <button onclick="document.getElementById('mAdd').classList.remove('hidden')" class="bg-amber-500 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all">Tambah Gudang</button>
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
                    <th class="px-8 py-6">ID</th>
                    <th class="px-8 py-6">Nama Gudang</th>
                    <th class="px-8 py-6">Jenis</th>
                    <th class="px-8 py-6">Alamat</th>
                    <th class="px-8 py-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach($gudangs as $g): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-8 py-6 font-black text-navy-900">#<?= htmlspecialchars($g['id_gudang']) ?></td>
                    <td class="px-8 py-6 font-bold text-slate-700"><?= htmlspecialchars($g['nama_gudang']) ?></td>
                    <td class="px-8 py-6 text-slate-500">
                        <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full <?= $g['jenis'] == 'Pusat' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' ?>"><?= htmlspecialchars($g['jenis']) ?></span>
                    </td>
                    <td class="px-8 py-6 text-slate-400 italic text-xs"><?= htmlspecialchars($g['alamat']) ?></td>
                    <td class="px-8 py-6 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick='editGudang(<?= json_encode($g) ?>)' class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                            <a href="?delete=<?= $g['id_gudang'] ?>" onclick="return confirm('Hapus gudang ini?')" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit -->
<div id="mAdd" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg mx-4 overflow-hidden animate-fade-in">
        <div class="px-10 py-8 border-b border-slate-50 bg-amber-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-amber-900" id="mTitle">Tambah Gudang</h3>
            <button onclick="closeModal()" class="text-amber-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <input type="hidden" name="action" id="mAction" value="add">
            <input type="hidden" name="id_gudang" id="mId">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Nama Gudang</label>
                <input type="text" name="nama_gudang" id="mNama" required placeholder="Contoh: Gudang Cabang B" class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Jenis</label>
                <select name="jenis" id="mJenis" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="Pusat">Pusat</option>
                    <option value="Cabang">Cabang</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Alamat Lengkap</label>
                <textarea name="alamat" id="mAlamat" rows="3" required placeholder="Jl. Raya No. 123..." class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500"></textarea>
            </div>
            <button type="submit" class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Simpan Data</button>
        </form>
    </div>
</div>

<script>
    function closeModal() { document.getElementById('mAdd').classList.add('hidden'); }
    function editGudang(g) {
        document.getElementById('mTitle').innerText = "Edit Gudang";
        document.getElementById('mAction').value = "edit";
        document.getElementById('mId').value = g.id_gudang;
        document.getElementById('mNama').value = g.nama_gudang;
        document.getElementById('mJenis').value = g.jenis;
        document.getElementById('mAlamat').value = g.alamat;
        document.getElementById('mAdd').classList.remove('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
