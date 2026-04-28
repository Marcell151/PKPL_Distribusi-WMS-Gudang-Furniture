<?php
require 'config.php';
require_access(['Admin']);

// Handlers for Add, Edit, Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $nama = $_POST['nama_lengkap'];
        $role = $_POST['role'];
        try {
            $stmt = $pdo->prepare("INSERT INTO tb_users (nama_lengkap, role) VALUES (?, ?)");
            $stmt->execute([$nama, $role]);
            $success = "Pengguna baru berhasil ditambahkan.";
        } catch (PDOException $e) { $error = $e->getMessage(); }
    } elseif (isset($_POST['edit'])) {
        $id = $_POST['id_user'];
        $nama = $_POST['nama_lengkap'];
        $role = $_POST['role'];
        try {
            $stmt = $pdo->prepare("UPDATE tb_users SET nama_lengkap = ?, role = ? WHERE id_user = ?");
            $stmt->execute([$nama, $role, $id]);
            $success = "Data pengguna berhasil diubah.";
        } catch (PDOException $e) { $error = $e->getMessage(); }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id_user'];
        if ($id == $_SESSION['user']['id_user']) {
            $error = "Anda tidak dapat menghapus akun Anda sendiri yang sedang login.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM tb_users WHERE id_user = ?");
                $stmt->execute([$id]);
                $success = "Pengguna berhasil dihapus.";
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM tb_users ORDER BY role ASC, nama_lengkap ASC");
$users = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Manajemen Pengguna</h2>
            <p class="text-slate-500 font-medium mt-1">Kelola akses dan akun staf sistem WMS.</p>
        </div>
        <button onclick="document.getElementById('mAdd').classList.remove('hidden')" class="bg-navy-900 text-white py-4 px-8 rounded-2xl font-bold text-sm shadow-xl shadow-navy-900/10 hover:bg-navy-800 transition-all">Tambah Pengguna</button>
    </header>

    <?php if(isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 font-bold text-sm"><?= $error ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-6">ID Pengguna</th>
                        <th class="px-8 py-6">Nama Lengkap</th>
                        <th class="px-8 py-6">Role / Peran</th>
                        <th class="px-8 py-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php foreach($users as $u): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6 font-black text-slate-400">USR-<?= str_pad($u['id_user'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td class="px-8 py-6 font-bold text-navy-900 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-black text-xs uppercase"><?= substr($u['nama_lengkap'], 0, 2) ?></div>
                            <?= htmlspecialchars($u['nama_lengkap']) ?>
                        </td>
                        <td class="px-8 py-6">
                            <?php 
                                $rc = 'bg-slate-100 text-slate-600';
                                if($u['role'] == 'Admin') $rc = 'bg-navy-100 text-navy-700';
                                elseif($u['role'] == 'Supervisor') $rc = 'bg-amber-100 text-amber-700';
                                elseif($u['role'] == 'Staff Gudang') $rc = 'bg-blue-100 text-blue-700';
                            ?>
                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $rc ?>"><?= $u['role'] ?></span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <button onclick='oe(<?= json_encode($u) ?>)' class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-200 transition-colors mr-2">Edit</button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Hapus pengguna ini?');">
                                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                <button type="submit" name="delete" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-xs font-bold hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div id="mAdd" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="px-10 py-8 border-b border-slate-50 bg-slate-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-navy-900">Tambah Pengguna</h3>
            <button onclick="document.getElementById('mAdd').classList.add('hidden')" class="text-slate-400 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required placeholder="Nama Lengkap..." class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-navy-900">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Hak Akses / Role</label>
                <select name="role" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-navy-900 appearance-none">
                    <option value="Admin">Admin</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Staff Gudang">Staff Gudang</option>
                </select>
            </div>
            <button type="submit" name="add" class="w-full py-5 rounded-2xl bg-navy-900 text-white font-black text-sm shadow-xl shadow-navy-900/20 hover:bg-navy-800 transition-all uppercase tracking-widest">Simpan Pengguna</button>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="mEdit" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="px-10 py-8 border-b border-slate-50 bg-amber-50 flex justify-between items-center">
            <h3 class="font-black text-2xl text-amber-900">Edit Pengguna</h3>
            <button onclick="document.getElementById('mEdit').classList.add('hidden')" class="text-amber-800 text-3xl">&times;</button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <input type="hidden" name="id_user" id="e_id">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="e_nama" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">Hak Akses / Role</label>
                <select name="role" id="e_role" required class="w-full bg-slate-50 rounded-2xl p-4 text-sm font-bold text-navy-900 outline-none focus:ring-2 focus:ring-amber-500 appearance-none">
                    <option value="Admin">Admin</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Staff Gudang">Staff Gudang</option>
                </select>
            </div>
            <button type="submit" name="edit" class="w-full py-5 rounded-2xl bg-amber-500 text-white font-black text-sm shadow-xl shadow-amber-500/20 hover:bg-amber-600 transition-all uppercase tracking-widest">Update Pengguna</button>
        </form>
    </div>
</div>

<script>
    function oe(d) {
        document.getElementById('e_id').value = d.id_user;
        document.getElementById('e_nama').value = d.nama_lengkap;
        document.getElementById('e_role').value = d.role;
        document.getElementById('mEdit').classList.remove('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
