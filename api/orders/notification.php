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

if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();
}

$serverKey = getenv('MIDTRANS_SERVER_KEY') ?: ($_ENV['MIDTRANS_SERVER_KEY'] ?? 'Mid-server-A81yoD44pqELw32rIeeqXDH0');
$isProduction = (getenv('MIDTRANS_IS_PRODUCTION') === 'true' || ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? '') === 'true');

\Midtrans\Config::$serverKey = $serverKey;
\Midtrans\Config::$isProduction = $isProduction;

try {
    $rawNotification = file_get_contents('php://input');
    $notif = json_decode($rawNotification);

    if (!$notif) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Payload JSON tidak valid"]);
        exit();
    }

    $transaction = $notif->transaction_status ?? '';
    $type        = $notif->payment_type ?? '';
    $order_id    = $notif->order_id ?? '';
    $fraud       = $notif->fraud_status ?? '';
    $statusCode  = $notif->status_code ?? '';
    $grossAmount = $notif->gross_amount ?? '';
    $signature   = $notif->signature_key ?? '';

    $calcSignature = hash("sha512", $order_id . $statusCode . $grossAmount . $serverKey);
    if ($calcSignature !== $signature) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Signature Key tidak cocok"]);
        exit();
    }

    $pdo = getDbConnection();

    $stmtCheck = $pdo->prepare("SELECT * FROM orders WHERE order_number = :order_number");
    $stmtCheck->execute(['order_number' => $order_id]);
    $currentOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$currentOrder) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Pesanan tidak ditemukan"]);
        exit();
    }

    $internalOrderId = $currentOrder['order_id'] ?? $currentOrder['id'] ?? null;
    $paymentStatus = 'pending';
    $orderStatus   = 'pending';
    $shouldRestock = false;

    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                $paymentStatus = 'pending';
                $orderStatus   = 'pending';
            } else {
                $paymentStatus = 'paid';
                $orderStatus   = 'processing';
            }
        }
    } else if ($transaction == 'settlement') {
        $paymentStatus = 'paid';
        $orderStatus   = 'processing';
    } else if ($transaction == 'pending') {
        $paymentStatus = 'pending';
        $orderStatus   = 'pending';
    } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
        $paymentStatus = 'failed';
        $orderStatus   = 'cancelled';
        
        if ($currentOrder['order_status'] !== 'cancelled') {
            $shouldRestock = true;
        }
    }

    $pdo->beginTransaction();

    $stmtUpdate = $pdo->prepare("
        UPDATE orders 
        SET payment_status = :payment_status, 
            order_status = :order_status, 
            updated_at = NOW() 
        WHERE order_number = :order_number
    ");
    $stmtUpdate->execute([
        'payment_status' => $paymentStatus,
        'order_status'   => $orderStatus,
        'order_number'   => $order_id
    ]);

    if ($shouldRestock && $internalOrderId) {
        restockOrderItems($pdo, $internalOrderId);
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Notifikasi berhasil diproses"]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}