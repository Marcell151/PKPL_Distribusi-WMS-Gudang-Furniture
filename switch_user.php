<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM tb_users WHERE id_user = ?");
    $stmt->execute([$_POST['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user'] = $user;
    }
}
header("Location: dashboard.php");
exit;
?>
