<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/database.php';

$customerPhone = trim($_GET['phone'] ?? '');

if (empty($customerPhone)) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor HP dibutuhkan']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM chats 
        WHERE customer_phone = :phone 
        ORDER BY created_at ASC
    ");
    $stmt->execute(['phone' => $customerPhone]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $messages]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}