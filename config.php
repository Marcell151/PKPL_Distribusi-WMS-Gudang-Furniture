<?php
session_start();

$db_file = __DIR__ . '/wms_furni.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// User Mockup / Switcher Logic
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id_user' => 3,
        'nama_lengkap' => 'Budi Santoso',
        'role' => 'Staff Gudang'
    ];
}

function check_access($allowed_roles) {
    if (!isset($_SESSION['user'])) return false;
    return in_array($_SESSION['user']['role'], $allowed_roles);
}

function require_access($allowed_roles) {
    if (!check_access($allowed_roles)) {
        echo "<script>alert('Akses Ditolak. Role Anda tidak memiliki izin untuk halaman ini.'); window.location.href='dashboard.php';</script>";
        exit;
    }
}
?>
