<aside class="w-72 sidebar-gradient text-white flex flex-col hidden lg:flex h-full border-r border-navy-800 shadow-2xl z-20">
    <!-- Brand Logo -->
    <div class="h-24 flex items-center px-8 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/20 rotate-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-extrabold tracking-tighter uppercase">WMS<span class="text-amber-500">-Furni</span></h1>
                <p class="text-[10px] text-blue-300 font-bold uppercase tracking-widest leading-none">Distributor Edition</p>
            </div>
        </div>
    </div>
    
    <!-- User Profile Switcher Card -->
    <div class="m-4 p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
        <div class="flex items-center gap-3 mb-4">
            <div class="relative">
                <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-amber-500 to-orange-400 p-[2px]">
                    <div class="w-full h-full rounded-full bg-navy-900 flex items-center justify-center text-lg font-bold text-white">
                        <?= substr($_SESSION['user']['nama_lengkap'], 0, 1) ?>
                    </div>
                </div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-navy-900 rounded-full"></div>
            </div>
            <div>
                <p class="text-xs font-bold text-white truncate w-32"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?></p>
                <p class="text-[10px] text-blue-300 font-medium"><?= htmlspecialchars($_SESSION['user']['role']) ?></p>
            </div>
        </div>

        <form action="switch_user.php" method="POST">
            <select name="user_id" onchange="this.form.submit()" class="w-full bg-white text-navy-900 text-xs font-bold p-3 rounded-xl border border-white/20 outline-none focus:ring-2 focus:ring-amber-500 transition-all cursor-pointer shadow-inner">
                <option value="" disabled selected class="text-slate-400">Pindah Akun...</option>
                <option value="1" <?= $_SESSION['user']['id_user'] == 1 ? 'selected' : '' ?> class="text-navy-900">Andi (Admin)</option>
                <option value="2" <?= $_SESSION['user']['id_user'] == 2 ? 'selected' : '' ?> class="text-navy-900">Siti (Supervisor)</option>
                <option value="3" <?= $_SESSION['user']['id_user'] == 3 ? 'selected' : '' ?> class="text-navy-900">Budi (Staff)</option>
            </select>
        </form>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-4 py-2 space-y-6 scrollbar-hide">
        <!-- Main Group -->
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Core Modules</p>
            <ul class="space-y-1">
                <li>
                    <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="text-sm font-semibold">Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <?php if (check_access(['Admin'])): ?>
        <!-- Master Group -->
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Master Data</p>
            <ul class="space-y-1">
                <li>
                    <a href="master_furniture.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'master_furniture.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span class="text-sm font-semibold">Furniture</span>
                    </a>
                </li>
                <li>
                    <a href="master_supplier.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'master_supplier.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span class="text-sm font-semibold">Supplier</span>
                    </a>
                </li>
                <li>
                    <a href="master_toko.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'master_toko.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="text-sm font-semibold">Pelanggan/Toko</span>
                    </a>
                </li>
                <li>
                    <a href="master_lokasi.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'master_lokasi.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="text-sm font-semibold">Lokasi Gudang</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Inventory Group -->
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Operations</p>
            <ul class="space-y-1">
                <?php if (check_access(['Admin', 'Staff Gudang'])): ?>
                <li>
                    <a href="inbound.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'inbound.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        <span class="text-sm font-semibold">Inbound & Putaway</span>
                    </a>
                </li>
                <li>
                    <a href="sales_order.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'sales_order.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span class="text-sm font-semibold">Request SO</span>
                    </a>
                </li>
                <li>
                    <a href="outbound.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'outbound.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        <span class="text-sm font-semibold">Outbound & QC</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Inventory Control</p>
            <ul class="space-y-1">
                <li>
                    <a href="inventory.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-sm font-semibold">Manajemen Inventory</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- System Group -->
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Analytical & Sys</p>
            <ul class="space-y-1">
                <?php if (check_access(['Admin', 'Supervisor'])): ?>
                <li>
                    <a href="laporan.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span class="text-sm font-semibold">Laporan Terpadu</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (check_access(['Admin'])): ?>
                <li>
                    <a href="manajemen_pengguna.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'manajemen_pengguna.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="text-sm font-semibold">Kelola Akun</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="documentation.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'documentation.php' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <span class="text-sm font-semibold">Dokumentasi</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <!-- Footer Sidebar -->
    <div class="p-6 border-t border-white/10">
        <div class="flex items-center justify-between text-[10px] text-blue-300/50 font-bold uppercase tracking-widest">
            <span>v2.0 Premium</span>
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
        </div>
    </div>
</aside>
<main class="flex-1 flex flex-col h-full overflow-hidden relative">
    <!-- Header Top Bar -->
    <div class="h-20 glass border-b border-slate-200 flex items-center justify-between px-8 z-10">
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Warehouse Management System</p>
            <h2 class="text-xl font-extrabold text-navy-900 tracking-tight">WMS-Furni Corporate</h2>
        </div>
        <div class="flex items-center gap-6">
            <div class="hidden md:flex flex-col items-end">
                <p class="text-xs font-bold text-navy-900"><?= date('l, d F Y') ?></p>
                <p class="text-[10px] text-slate-500" id="live-clock"><?= date('H:i:s') ?></p>
            </div>
            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-navy-900 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </div>
        </div>
    </div>
    <script>
        setInterval(() => {
            const now = new Date();
            document.getElementById('live-clock').innerText = now.toLocaleTimeString('id-ID');
        }, 1000);
    </script>
