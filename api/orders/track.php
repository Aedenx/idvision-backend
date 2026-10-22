<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

$pdo = getDbConnection();
$order_number = trim($_GET['order_number'] ?? '');

if (empty($order_number)) {
    echo json_encode(["status" => "error", "message" => "Nomor pesanan tidak boleh kosong"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT order_id, order_number, customer_name, customer_email, customer_phone,
               shipping_address, total_amount, payment_method, payment_status, 
               order_status, tracking_number, shipping_notes, created_at, updated_at
        FROM orders 
        WHERE order_number = :order_number
    ");
    $stmt->execute(['order_number' => $order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(["status" => "error", "message" => "Pesanan dengan nomor tersebut tidak ditemukan"]);
        exit;
    }

    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmtItems->execute(['order_id' => $order['order_id']]);
    $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data"   => $order
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data: " . $e->getMessage()]);
}