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

if (!empty($data['email']) && !empty($data['password'])) {
    // KOREKSI: Menggunakan nama kolom database full_name & password_hash
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, password_hash, is_active FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => trim($data['email'])]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_active'] == 0) {
            echo json_encode([
                "success" => false,
                "message" => "Akun Anda sedang dinonaktifkan."
            ]);
            exit;
        }

        if (password_verify($data['password'], $user['password_hash'])) {
            echo json_encode([
                "success" => true,
                "message" => "Login berhasil!",
                "token"   => bin2hex(random_bytes(16)),
                "user"    => [
                    "id"    => $user['user_id'],
                    "name"  => $user['full_name'],
                    "email" => $user['email'],
                    "phone" => $user['phone'] ?? ''
                ]
            ]);
            exit;
        }
    }
}

echo json_encode([
    "success" => false,
    "message" => "Email atau kata sandi tidak cocok."
]);