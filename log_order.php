<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id = isset($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : 0;
    $customer_name = trim($_POST['customer_name']) ?: 'Guest Customer';
    $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
    // Expecting cart items JSON payload from JavaScript
    $cart_items = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];

    if ($vendor_id > 0 && $total_amount > 0 && !empty($cart_items)) {
        try {
            $pdo->beginTransaction();

            // 1. Build string summary of details for the order log
            $detailsArr = [];
            foreach ($cart_items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $detailsArr[] = "{$item['name']} x {$item['quantity']} (\${$subtotal})";
            }
            $order_details = implode(', ', $detailsArr);

            // 2. Insert into orders table
            $stmt = $pdo->prepare("INSERT INTO orders (vendor_id, customer_name, total_amount, order_details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$vendor_id, $customer_name, $total_amount, $order_details]);

            // 3. Automatically deduct stock for each item in the cart
            $stmtStock = $pdo->prepare("UPDATE menu_items SET stock = GREATEST(0, stock - ?) WHERE id = ? AND vendor_id = ?");
            foreach ($cart_items as $item) {
                $itemId = (int)$item['id'];
                $qty = (int)$item['quantity'];
                $stmtStock->execute([$qty, $itemId, $vendor_id]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
echo json_encode(['success' => false]);