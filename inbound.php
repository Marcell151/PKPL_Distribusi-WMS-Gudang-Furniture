<?php
require 'config.php';
require_access(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_po = $_POST['no_po'];
    $id_supplier = $_POST['id_supplier'];
    $id_furniture = $_POST['id_furniture'];
    $qty_dipesan = (int)$_POST['qty_dipesan'];
    $qty_fisik = (int)$_POST['qty_fisik'];
    $keterangan_refund = $_POST['keterangan_refund'] ?? '';
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE tb_furniture SET stok_tersedia = stok_tersedia + ? WHERE id_furniture = ?");
        $stmt->execute([$qty_fisik, $id_furniture]);
        
        $stmt_sup_name = $pdo->prepare("SELECT nama_supplier FROM tb_supplier WHERE id_supplier = ?");
        $stmt_sup_name->execute([$id_supplier]);
        $sup_name = $stmt_sup_name->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO tb_mutasi_stok (id_furniture, tgl_mutasi, jenis_mutasi, qty, keterangan) VALUES (?, datetime('now', 'localtime'), 'IN', ?, ?)");
        $stmt->execute([$id_furniture, $qty_fisik, "Penerimaan PO: $no_po dari " . $sup_name]);
        if ($qty_fisik < $qty_dipesan) {
            $qty_kurang = $qty_dipesan - $qty_fisik;
            $stmt = $pdo->prepare("INSERT INTO tb_nota_selisih (no_po_supplier, id_furniture, qty_kurang, keterangan_refund) VALUES (?, ?, ?, ?)");
            $stmt->execute([$no_po, $id_furniture, $qty_kurang, $keterangan_refund]);
        }
        $pdo->commit();
        $success = "Inbound berhasil diproses!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT f.*, l.nama_blok, l.rak FROM tb_furniture f LEFT JOIN tb_lokasi l ON f.id_lokasi = l.id_lokasi ORDER BY f.nama_barang ASC");
$furniture_list = $stmt->fetchAll();

$stmt_sup = $pdo->query("SELECT * FROM tb_supplier ORDER BY nama_supplier ASC");
$suppliers = $stmt_sup->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-8 animate-fade-in">
    <header class="mb-10">
        <h2 class="text-3xl font-extrabold text-navy-900 tracking-tight">Penerimaan Barang</h2>
        <p class="text-slate-500 font-medium mt-1">Logistik Inbound: Validasi fisik vs PO supplier.</p>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-10">
            <form method="POST" action="inbound.php" class="space-y-8">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Supplier</label>
                        <select name="id_supplier" required class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none appearance-none cursor-pointer">
                            <option value="" disabled selected>-- Vendor --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Nomor PO</label>
                        <input type="text" name="no_po" required placeholder="PO-202X-XXX" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Pilih Produk</label>
                    <select name="id_furniture" id="id_furniture" required onchange="showPutaway()" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none appearance-none cursor-pointer">
                        <option value="" disabled selected>-- Pilih SKU Furniture --</option>
                        <?php foreach($furniture_list as $f): ?>
                            <option value="<?= $f['id_furniture'] ?>" data-lokasi="<?= htmlspecialchars($f['nama_blok'] ?? 'N/A') ?> - <?= htmlspecialchars($f['rak'] ?? 'N/A') ?>"><?= htmlspecialchars($f['kode_barang']) ?> - <?= htmlspecialchars($f['nama_barang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="putaway_info" class="hidden bg-blue-50 border border-blue-200 p-6 rounded-2xl">
                    <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Instruksi Putaway</p>
                    <p class="text-sm font-bold text-blue-900">Simpan di: <span id="lokasi_text" class="text-lg font-black bg-white px-2 py-1 rounded shadow-sm ml-2"></span></p>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Qty Pesanan</label>
                        <input type="number" id="qty_dipesan" name="qty_dipesan" min="1" required class="w-full bg-slate-50 border-none rounded-2xl p-5 text-lg font-black text-navy-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Qty Aktual</label>
                        <input type="number" id="qty_fisik" name="qty_fisik" min="0" required class="w-full bg-blue-50 border-none rounded-2xl p-5 text-lg font-black text-blue-600 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <!-- Refund Area -->
                <div id="refund_section" class="hidden animate-fade-in p-8 rounded-3xl bg-red-50 border border-red-100">
                    <div class="flex items-center gap-3 mb-4 text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <h4 class="font-black text-sm uppercase tracking-wider">Nota Selisih Terdeteksi</h4>
                    </div>
                    <textarea name="keterangan_refund" id="keterangan_refund" rows="3" placeholder="Jelaskan alasan selisih barang (pecah, kurang kirim, dll)..." class="w-full bg-white border-none rounded-2xl p-5 text-sm font-medium text-red-900 focus:ring-2 focus:ring-red-500 outline-none"></textarea>
                </div>

                <button type="submit" class="w-full py-5 rounded-2xl bg-navy-900 text-white font-black text-sm shadow-2xl shadow-navy-900/20 hover:bg-navy-800 transition-all tracking-widest uppercase">Proses Inbound</button>
            </form>
        </div>

        <div class="hidden lg:block space-y-6">
            <div class="bg-blue-900 rounded-[2.5rem] p-10 text-white relative overflow-hidden shadow-2xl shadow-blue-900/20">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM5.884 6.98L4.47 5.566a1 1 0 00-1.414 1.414l1.414 1.414A1 1 0 005.884 6.98zM14.116 6.98a1 1 0 011.414-1.414l1.414 1.414a1 1 0 01-1.414 1.414l-1.414-1.414zM3 11a1 1 0 100-2H2a1 1 0 100 2h1zM18 11a1 1 0 100-2h-1a1 1 0 100 2h1zM5.884 13.02a1 1 0 10-1.414 1.414l1.414 1.414a1 1 0 101.414-1.414l-1.414-1.414zM14.116 13.02a1 1 0 011.414 1.414l1.414 1.414a1 1 0 01-1.414-1.414l-1.414-1.414zM11 15a1 1 0 10-2 0v1a1 1 0 102 0v-1z"></path></svg>
                </div>
                <h3 class="text-2xl font-black mb-4 tracking-tight">Standar Operasional</h3>
                <ul class="space-y-4 text-blue-200 text-sm font-medium">
                    <li class="flex items-start gap-3"><span class="w-5 h-5 rounded-full bg-amber-500 text-navy-900 flex items-center justify-center text-[10px] font-black flex-shrink-0">1</span> Pastikan PO Supplier sesuai dengan surat jalan.</li>
                    <li class="flex items-start gap-3"><span class="w-5 h-5 rounded-full bg-amber-500 text-navy-900 flex items-center justify-center text-[10px] font-black flex-shrink-0">2</span> Jika barang pecah saat bongkar, masukkan ke stok karantina via Opname nantinya.</li>
                    <li class="flex items-start gap-3"><span class="w-5 h-5 rounded-full bg-amber-500 text-navy-900 flex items-center justify-center text-[10px] font-black flex-shrink-0">3</span> Nota selisih akan otomatis tercatat untuk bagian Finance.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    const i1 = document.getElementById('qty_dipesan'), i2 = document.getElementById('qty_fisik'), rs = document.getElementById('refund_section'), ir = document.getElementById('keterangan_refund');
    function c() {
        const p = parseInt(i1.value)||0, f = parseInt(i2.value)||0;
        if(f < p && p > 0) { rs.classList.remove('hidden'); ir.required = true; }
        else { rs.classList.add('hidden'); ir.required = false; }
    }
    i1.addEventListener('input', c); i2.addEventListener('input', c);
    
    function showPutaway() {
        const sel = document.getElementById('id_furniture');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value !== "") {
            document.getElementById('putaway_info').classList.remove('hidden');
            document.getElementById('lokasi_text').innerText = opt.getAttribute('data-lokasi');
        } else {
            document.getElementById('putaway_info').classList.add('hidden');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
