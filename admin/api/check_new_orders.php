<?php
header('Content-Type: application/json');
require_once '../../backend/config/database.php';

try {
    $pdo = getDbConnection();
    
    // Hitung pesanan dengan status 'pending' atau 'processing'
    $stmt = $pdo->query("SELECT COUNT(*) AS total_new, MAX(order_id) AS last_id FROM orders WHERE order_status IN ('pending', 'processing')");
    $result = $stmt->fetch();

    echo json_encode([
        'success'   => true,
        'total_new' => intval($result['total_new'] ?? 0),
        'last_id'   => intval($result['last_id'] ?? 0)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>