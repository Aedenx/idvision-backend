<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $orderNumber = $input['order_number'] ?? '';

    if (empty($orderNumber)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nomor pesanan wajib diisi"]);
        exit();
    }

    $pdo = getDbConnection();

    // 1. Ambil data pesanan berdasarkan order_number
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :order_number");
    $stmt->execute(['order_number' => $orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Pesanan tidak ditemukan"]);
        exit();
    }

    // Validasi status: Pembatalan hanya diizinkan untuk status 'pending' atau 'processing'
    if (!in_array($order['order_status'], ['pending', 'processing'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Pesanan tidak dapat dibatalkan karena sudah dikirim atau selesai"
        ]);
        exit();
    }

    $internalOrderId = $order['order_id'] ?? $order['id'] ?? null;

    // 2. DETEKSI DUKUNGAN KOLOM payment_status AGAR TIDAK TRUNCATED
    $stmtCol = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
    $colInfo = $stmtCol->fetch(PDO::FETCH_ASSOC);
    $typeType = $colInfo['Type'] ?? '';

    $targetRefundStatus = 'refund_pending';

    // Jika tipe data ENUM dan 'refund_pending' belum ada di dalam opsi ENUM, gunakan fallback 'cancelled' atau 'ref_pending'
    if (strpos($typeType, 'enum') !== false) {
        preg_match_all("/'([^']+)'/", $typeType, $matches);
        $enumOptions = $matches[1] ?? [];

        if (!in_array('refund_pending', $enumOptions)) {
            if (in_array('ref_pending', $enumOptions)) {
                $targetRefundStatus = 'ref_pending';
            } else {
                $targetRefundStatus = 'cancelled'; // Fallback jika ENUM tidak memiliki opsi refund
            }
        }
    }

    // Tentukan status bayar baru jika pesanan sebelumnya berstatus paid
    $newPaymentStatus = ($order['payment_status'] === 'paid') ? $targetRefundStatus : 'cancelled';

    $pdo->beginTransaction();

    // 3. Update order_status dan payment_status
    $stmtUpdate = $pdo->prepare("
        UPDATE orders 
        SET order_status = 'cancelled', 
            payment_status = :payment_status,
            updated_at = NOW() 
        WHERE order_number = :order_number
    ");
    $stmtUpdate->execute([
        'payment_status' => $newPaymentStatus,
        'order_number'   => $orderNumber
    ]);

    // 4. Restock stok barang menggunakan helper
    restockOrderItems($pdo, $internalOrderId);

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        "status" => "success", 
        "message" => "Pesanan berhasil dibatalkan"
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}