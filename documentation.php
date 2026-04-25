<?php
require 'config.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto p-6 md:p-8">
    <header class="mb-10">
        <h2 class="text-3xl font-bold text-navy-900">Sistem Dokumentasi WMS-Furni</h2>
        <p class="text-slate-500 mt-1">Panduan Arsitektur, Use Case, dan Alur Bisnis untuk Pengembang & Auditor.</p>
    </header>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        
        <!-- Use Case Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-3 bg-amber-100 rounded-lg text-amber-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-navy-900">Diagram Use Case</h3>
            </div>
            
            <div class="space-y-6">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-sm font-bold text-navy-900 mb-2">1. Staff Gudang (Operational Role)</p>
                    <ul class="text-sm text-slate-600 space-y-1 list-disc pl-5">
                        <li>Melakukan Inbound (Penerimaan Barang).</li>
                        <li>Membuat Request Sales Order (SO) Cabang.</li>
                        <li>Melakukan QC Pre-Delivery & Pengiriman (Outbound).</li>
                        <li>Melihat Kartu Stok & History Mutasi.</li>
                    </ul>
                </div>
                
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-sm font-bold text-navy-900 mb-2">2. Supervisor (Monitoring Role)</p>
                    <ul class="text-sm text-slate-600 space-y-1 list-disc pl-5">
                        <li>Melakukan Penyesuaian Stok (Stock Opname).</li>
                        <li>Melihat Laporan Konsolidasi & Pergerakan Barang.</li>
                        <li>Melakukan Audit Nota Selisih/Refund Supplier.</li>
                    </ul>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-sm font-bold text-navy-900 mb-2">3. Admin (System Role)</p>
                    <ul class="text-sm text-slate-600 space-y-1 list-disc pl-5">
                        <li>Mengelola Master Data Furniture (Tambah/Edit).</li>
                        <li>Mengelola Data User & Akses Sistem.</li>
                        <li>Akses penuh ke seluruh modul pelaporan.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Logic & Business Rules Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-3 bg-blue-100 rounded-lg text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-navy-900">Aturan Bisnis (Strict Rules)</h3>
            </div>
            
            <div class="grid grid-cols-1 gap-4">
                <div class="flex gap-4 p-4 hover:bg-slate-50 rounded-xl transition-colors">
                    <div class="w-8 h-8 rounded-full bg-navy-900 text-white flex-shrink-0 flex items-center justify-center font-bold">1</div>
                    <div>
                        <p class="font-bold text-navy-900">Alur Inbound & Nota Selisih</p>
                        <p class="text-sm text-slate-500">Jika Qty Fisik < Qty Dipesan, sistem secara paksa mewajibkan pengisian alasan refund dan mencatat Nota Selisih untuk klaim ke Supplier.</p>
                    </div>
                </div>

                <div class="flex gap-4 p-4 hover:bg-slate-50 rounded-xl transition-colors">
                    <div class="w-8 h-8 rounded-full bg-navy-900 text-white flex-shrink-0 flex items-center justify-center font-bold">2</div>
                    <div>
                        <p class="font-bold text-navy-900">Quality Control Pre-Delivery</p>
                        <p class="text-sm text-slate-500">Barang tidak bisa keluar tanpa proses QC. Jika Gagal QC, barang otomatis masuk ke 'Stok Karantina' dan tidak memotong stok untuk SO tersebut.</p>
                    </div>
                </div>

                <div class="flex gap-4 p-4 hover:bg-slate-50 rounded-xl transition-colors">
                    <div class="w-8 h-8 rounded-full bg-navy-900 text-white flex-shrink-0 flex items-center justify-center font-bold">3</div>
                    <div>
                        <p class="font-bold text-navy-900">Mutasi Internal & Kartu Stok</p>
                        <p class="text-sm text-slate-500">Setiap pergerakan barang (In, Out, Rusak, Opname) wajib terekam dalam Kartu Stok untuk menjamin audit trail yang lengkap.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Schema Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 xl:col-span-2">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-3 bg-green-100 rounded-lg text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-navy-900">Skema Database (SQLite3)</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100">
                            <th class="pb-4 font-semibold uppercase tracking-wider">Table Name</th>
                            <th class="pb-4 font-semibold uppercase tracking-wider">Primary Key</th>
                            <th class="pb-4 font-semibold uppercase tracking-wider">Key Fields</th>
                            <th class="pb-4 font-semibold uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-4 font-bold text-navy-900">tb_furniture</td>
                            <td class="py-4">id_furniture</td>
                            <td class="py-4">kode_barang, area_blok, stok_tersedia</td>
                            <td class="py-4 text-slate-500">Master data barang jadi furniture.</td>
                        </tr>
                        <tr>
                            <td class="py-4 font-bold text-navy-900">tb_sales_order</td>
                            <td class="py-4">id_so</td>
                            <td class="py-4">no_so, nama_toko, status</td>
                            <td class="py-4 text-slate-500">Header transaksi permintaan toko.</td>
                        </tr>
                        <tr>
                            <td class="py-4 font-bold text-navy-900">tb_mutasi_stok</td>
                            <td class="py-4">id_mutasi</td>
                            <td class="py-4">jenis_mutasi (IN/OUT/RUSAK), qty</td>
                            <td class="py-4 text-slate-500">Log history pergerakan stok barang.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
