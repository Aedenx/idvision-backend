<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    $vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
}

if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

require_once __DIR__ . '/../../config/database.php';

// Import helper WhatsApp & Email jika tersedia
if (file_exists(__DIR__ . '/../../helpers/whatsapp.php')) {
    require_once __DIR__ . '/../../helpers/whatsapp.php';
}
if (file_exists(__DIR__ . '/../../helpers/email.php')) {
    require_once __DIR__ . '/../../helpers/email.php';
}

// Konfigurasi Midtrans Sandbox
\Midtrans\Config::$serverKey = 'Mid-server-A81yoD44pqELw32rIeeqXDH0';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$pdo = getDbConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Data JSON tidak valid"]);
    exit;
}

$customer_name  = trim($data['customer_name'] ?? '');
$customer_email = trim($data['customer_email'] ?? '');
$customer_phone = trim($data['customer_phone'] ?? '');
$address        = trim($data['address'] ?? '');
$payment_method = trim($data['payment_method'] ?? 'midtrans'); // 'midtrans' atau 'cod'
$items          = $data['items'] ?? [];

if (empty($customer_name) || empty($customer_phone) || empty($address) || empty($items)) {
    echo json_encode(["status" => "error", "message" => "Mohon lengkapi seluruh data pemesan"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $total_amount = 0;
    $item_details = [];

    foreach ($items as $item) {
        $price = floatval($item['price'] ?? 0);
        $qty   = intval($item['quantity'] ?? 1);
        $subtotal = $price * $qty;
        $total_amount += $subtotal;

        $item_details[] = [
            'id'       => (string) ($item['product_id'] ?? $item['id']),
            'price'    => (int) $price,
            'quantity' => $qty,
            'name'     => substr($item['name'] ?? 'Kacamata', 0, 50)
        ];
    }

    $order_number = 'IDV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    // Atur status pesanan & pembayaran awal
    $payment_status = 'pending';
    $order_status   = ($payment_method === 'cod') ? 'processing' : 'pending';

    // 1. Simpan ke tabel orders
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (
            order_number, customer_name, customer_email, customer_phone, 
            shipping_address, total_amount, payment_method, payment_status, order_status
        ) VALUES (
            :order_number, :customer_name, :customer_email, :customer_phone, 
            :shipping_address, :total_amount, :payment_method, :payment_status, :order_status
        )
    ");

    $stmtOrder->execute([
        'order_number'     => $order_number,
        'customer_name'    => $customer_name,
        'customer_email'   => $customer_email,
        'customer_phone'   => $customer_phone,
        'shipping_address' => $address,
        'total_amount'     => $total_amount,
        'payment_method'   => $payment_method,
        'payment_status'   => $payment_status,
        'order_status'     => $order_status,
    ]);

    $order_id = $pdo->lastInsertId();

    // 2. Simpan rincian order_items
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, variant_id, product_name, price, quantity, subtotal
        ) VALUES (
            :order_id, :product_id, :variant_id, :product_name, :price, :quantity, :subtotal
        )
    ");

    // Query Potong Stok Varian
    $stmtUpdateVariantStock = $pdo->prepare("
        UPDATE product_variants 
        SET stock_quantity = GREATEST(0, stock_quantity - :qty) 
        WHERE variant_id = :variant_id
    ");

    // Query Potong Stok Varian Default berdasarkan product_id (jika variant_id tidak dikirim)
    $stmtUpdateDefaultVariantStock = $pdo->prepare("
        UPDATE product_variants 
        SET stock_quantity = GREATEST(0, stock_quantity - :qty) 
        WHERE product_id = :product_id
    ");

    foreach ($items as $item) {
        $product_id = intval($item['product_id'] ?? $item['id'] ?? 0);
        $variant_id = !empty($item['variant_id']) ? intval($item['variant_id']) : null;
        $qty        = intval($item['quantity'] ?? 1);

        $stmtItem->execute([
            'order_id'     => $order_id,
            'product_id'   => $product_id,
            'variant_id'   => $variant_id,
            'product_name' => $item['name'] ?? 'Kacamata',
            'price'        => floatval($item['price'] ?? 0),
            'quantity'     => $qty,
            'subtotal'     => floatval($item['price'] ?? 0) * $qty,
        ]);

        // Potong stok di product_variants
        if ($variant_id) {
            $stmtUpdateVariantStock->execute([
                'qty'        => $qty,
                'variant_id' => $variant_id,
            ]);
        } elseif ($product_id) {
            $stmtUpdateDefaultVariantStock->execute([
                'qty'        => $qty,
                'product_id' => $product_id,
            ]);
        }
    }

    $snapToken = null;

    // 3. Minta Snap Token dari Midtrans jika metode = 'midtrans'
    if ($payment_method === 'midtrans' && class_exists('\Midtrans\Snap')) {
        $params = [
            'transaction_details' => [
                'order_id'     => $order_number,
                'gross_amount' => (int) $total_amount,
            ],
            'customer_details' => [
                'first_name' => $customer_name,
                'email'      => $customer_email,
                'phone'      => $customer_phone,
            ],
            'item_details' => $item_details,
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);
    }

    // 4. Commit transaksi ke database MySQL
    $pdo->commit();

    // 5. Eksekusi Kirim Notifikasi WhatsApp Langsung & Andal
    if (function_exists('sendWhatsAppNotification') && !empty($customer_phone)) {
        sendWhatsAppNotification($customer_phone, $order_number, $customer_name, $total_amount, $payment_method, $items);
    }

    // Optional: Email notification jika aktif
    if (function_exists('sendEmailNotification') && !empty($customer_email)) {
        sendEmailNotification($customer_email, $customer_name, $order_number, $total_amount, $payment_method);
    }

    // 6. Kirim respons JSON ke frontend Next.js
    echo json_encode([
        "status"         => "success",
        "message"        => "Pesanan berhasil dibuat",
        "order_number"   => $order_number,
        "payment_method" => $payment_method,
        "snap_token"     => $snapToken
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal memproses pesanan: " . $e->getMessage()
    ]);
}