<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmtV = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmtV->execute([$user_id]);
$vendor = $stmtV->fetch();
$vendor_id = $vendor['id'];

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmtDel = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND vendor_id = ?");
$stmtDel->execute([$item_id, $vendor_id]);

header('Location: dashboard.php');
exit;
?>