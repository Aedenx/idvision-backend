<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();

// Email & Password murni
$email    = 'admin@idvision.com';
$password = 'admin123';

// Hash password segar dari PHP langsung
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

try {
    // Hapus user lama jika ada
    $stmtDel = $pdo->prepare("DELETE FROM users WHERE email = :e");
    $stmtDel->execute(['e' => $email]);

    // Insert user admin baru
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, full_name, role, is_active) 
        VALUES (:email, :pass, 'Administrator IDVISION', 'admin', 1)
    ");
    $stmt->execute([
        'email' => $email,
        'pass'  => $passwordHash
    ]);

    echo "<h2 style='color:green;'>Berhasil buat akun admin!</h2>";
    echo "<p>Email: <b>admin@idvision.com</b></p>";
    echo "<p>Password: <b>admin123</b></p>";
    echo "<a href='login.php'>Klik di sini untuk Login</a>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Gagal: " . $e->getMessage() . "</h2>";
}