<?php
require 'config.php';

try {
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tb_users'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        header("Location: init_db.php");
        exit;
    }
    header("Location: dashboard.php");
    exit;
} catch (Exception $e) {
    header("Location: init_db.php");
    exit;
}
?>
