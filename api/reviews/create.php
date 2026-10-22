<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

$pdo = getDbConnection();
$data = json_decode(file_get_contents("php://input"), true);

$order_id      = intval($data['order_id'] ?? 0);
$product_id    = intval($data['product_id'] ?? 0);
$customer_name = trim($data['customer_name'] ?? 'Pembeli');
$rating        = intval($data['rating'] ?? 5);
$comment       = trim($data['comment'] ?? '');

if (!$order_id || !$product_id || $rating < 1 || $rating > 5) {
    echo json_encode(["status" => "error", "message" => "Data ulasan tidak valid"]);
    exit;
}

try {
    // 1. Cek apakah pesanan memang sudah status 'completed'
    $stmtOrder = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = :order_id");
    $stmtOrder->execute(['order_id' => $order_id]);
    $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$order || strtolower($order['order_status']) !== 'completed') {
        echo json_encode(["status" => "error", "message" => "Ulasan hanya bisa diberikan jika pesanan telah selesai"]);
        exit;
    }

    // 2. Cek apakah produk ini sudah pernah diulas untuk pesanan ini
    $stmtCheck = $pdo->prepare("SELECT review_id FROM reviews WHERE order_id = :order_id AND product_id = :product_id");
    $stmtCheck->execute(['order_id' => $order_id, 'product_id' => $product_id]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["status" => "error", "message" => "Anda sudah memberikan ulasan untuk produk ini"]);
        exit;
    }

    // 3. Simpan Ulasan
    $stmtInsert = $pdo->prepare("
        INSERT INTO reviews (order_id, product_id, customer_name, rating, comment)
        VALUES (:order_id, :product_id, :customer_name, :rating, :comment)
    ");
    $stmtInsert->execute([
        'order_id'      => $order_id,
        'product_id'    => $product_id,
        'customer_name' => $customer_name,
        'rating'        => $rating,
        'comment'       => $comment
    ]);

    echo json_encode(["status" => "success", "message" => "Ulasan berhasil disimpan. Terima kasih!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan ulasan: " . $e->getMessage()]);
}