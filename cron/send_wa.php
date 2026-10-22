<?php
set_time_limit(0);
ignore_user_abort(true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/whatsapp.php';

$target       = $argv[1] ?? '';
$order_number = $argv[2] ?? '';

if (empty($target) || empty($order_number)) {
    exit("Data tidak lengkap\n");
}

try {
    $pdo = getDbConnection();

    // 1. Ambil data pesanan
    $stmt = $pdo->prepare("
        SELECT customer_name, total_amount, payment_method 
        FROM orders 
        WHERE order_number = :order_number
    ");
    $stmt->execute(['order_number' => $order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        exit("Pesanan tidak ditemukan\n");
    }

    // 2. Ambil rincian item barang
    $stmtItems = $pdo->prepare("
        SELECT product_name as name, quantity, price 
        FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        WHERE o.order_number = :order_number
    ");
    $stmtItems->execute(['order_number' => $order_number]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // 3. Kirim WhatsApp & Tampilkan Output Respons Fonnte
    if (function_exists('sendWhatsAppNotification')) {
        $response = sendWhatsAppNotification(
            $target,
            $order_number,
            $order['customer_name'],
            $order['total_amount'],
            $order['payment_method'],
            $items
        );

        echo "\n=== FONNTE API RESPONSE ===\n";
        echo $response . "\n";
        echo "===========================\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}