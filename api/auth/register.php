<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';
$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['name']) && !empty($data['email']) && !empty($data['password'])) {
    // Cek ketersediaan email
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => trim($data['email'])]);
    
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email sudah terdaftar!"]);
        exit;
    }

    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    // KOREKSI: Insert menyelaraskan kolom full_name & password_hash
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, is_active) VALUES (:full_name, :email, :phone, :password_hash, 'customer', 1)");
    $stmt->execute([
        'full_name'     => trim($data['name']),
        'email'         => trim($data['email']),
        'phone'         => trim($data['phone'] ?? ''),
        'password_hash' => $hashedPassword,
    ]);

    echo json_encode(["success" => true, "message" => "Registrasi berhasil!"]);
    exit;
}

echo json_encode(["success" => false, "message" => "Harap lengkapi semua data pendaftaran."]);