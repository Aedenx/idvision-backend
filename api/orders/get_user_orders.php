<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Dapatkan parameter email atau user_id dari URL
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';

// 2. Koneksi langsung ke database MySQL (Menghindari kegagalan require_once)
$host = "127.0.0.1";
$user = "root";
$pass = ""; // Sesuaikan jika MySQL kamu memakai password
$dbname = "ecommerce_kacamata";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Jika email dan user_id kosong, ambil 10 order terbaru secara langsung
    if (empty($email) && empty($user_id)) {
        $stmt = $pdo->prepare("SELECT * FROM orders ORDER BY order_id DESC LIMIT 10");
        $stmt->execute();
        $orders = $stmt->fetchAll();
    } else {
        // Query menggunakan pencarian string aman (LIKE & LOWER)
        $sql = "SELECT * FROM orders 
                WHERE LOWER(customer_email) LIKE LOWER(:email) 
                   OR (user_id = :user_id AND :user_id != '' AND :user_id != '0')
                ORDER BY order_id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":email", "%" . $email . "%");
        $stmt->bindValue(":user_id", $user_id);
        $stmt->execute();
        $orders = $stmt->fetchAll();
    }

    echo json_encode([
        "success" => true,
        "count" => count($orders),
        "data" => $orders
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi Database Gagal: " . $e->getMessage(),
        "data" => []
    ]);
}
?>