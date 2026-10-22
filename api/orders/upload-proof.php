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

$order_number = $_POST['order_number'] ?? '';

if (empty($order_number)) {
    echo json_encode(["status" => "error", "message" => "Nomor pesanan tidak valid"]);
    exit;
}

if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "File bukti transfer tidak ditemukan atau bermasalah"]);
    exit;
}

$file = $_FILES['payment_proof'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(["status" => "error", "message" => "Format file harus berupa JPG, PNG, atau WEBP"]);
    exit;
}

// Batas ukuran 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(["status" => "error", "message" => "Ukuran file maksimal 5MB"]);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'proof_' . preg_replace('/[^A-Za-z0-9\-]/', '', $order_number) . '_' . time() . '.' . $ext;

$uploadDir = '../../uploads/proofs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_proof = :proof, payment_status = 'pending' 
        WHERE order_number = :order_number
    ");
    $stmt->execute([
        'proof' => $filename,
        'order_number' => $order_number
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Bukti transfer berhasil diunggah",
        "filename" => $filename
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan file ke server"]);
}