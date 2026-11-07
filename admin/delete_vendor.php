<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT user_id FROM vendors WHERE id = ?");
$stmt->execute([$id]);
$vendor = $stmt->fetch();

if ($vendor) {
    $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmtDel->execute([$vendor['user_id']]);
}

header('Location: dashboard.php');
exit;
?>