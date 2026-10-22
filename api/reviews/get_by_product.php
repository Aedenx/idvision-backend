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
$product_id = intval($_GET['product_id'] ?? 0);

if (!$product_id) {
    echo json_encode(["status" => "error", "message" => "ID Produk tidak valid"]);
    exit;
}

try {
    // 1. Ambil Rata-rata Rating & Total Ulasan
    $stmtSummary = $pdo->prepare("
        SELECT 
            COALESCE(AVG(rating), 0) AS average_rating, 
            COUNT(review_id) AS total_reviews 
        FROM reviews 
        WHERE product_id = :product_id
    ");
    $stmtSummary->execute(['product_id' => $product_id]);
    $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    // 2. Ambil Daftar Ulasan Terbaru
    $stmtList = $pdo->prepare("
        SELECT customer_name, rating, comment, created_at 
        FROM reviews 
        WHERE product_id = :product_id 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmtList->execute(['product_id' => $product_id]);
    $reviews = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data"   => [
            "average_rating" => round(floatval($summary['average_rating']), 1),
            "total_reviews"  => intval($summary['total_reviews']),
            "reviews"        => $reviews
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil ulasan: " . $e->getMessage()]);
}