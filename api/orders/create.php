<?php
// backend/api/orders/create.php

require_once '../../config/cors.php';
require_once '../../config/database.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Invalid JSON Payload']);
    exit;
}

$customerName    = trim($data['customer_name'] ?? '');
$customerPhone   = trim($data['customer_phone'] ?? '');
$addressLine     = trim($data['shipping_address'] ?? '');
$city            = trim($data['city'] ?? 'Kota Pengirim');
$province        = trim($data['province'] ?? 'Provinsi');
$postalCode      = trim($data['postal_code'] ?? '00000');
$items           = $data['items'] ?? [];
$shippingCost    = (float)($data['shipping_cost'] ?? 0);

if (empty($customerName) || empty($customerPhone) || empty($addressLine) || empty($items)) {
    http_response_code(400);
    echo json_encode([
        'error' => true, 
        'message' => 'Nama, Nomor Telepon, Alamat Lengkap, dan Item Produk wajib diisi!'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $subtotalAmount = 0;
    $orderItemsPrepared = [];

    foreach ($items as $item) {
        $productId = (int)($item['id'] ?? 0);
        $qty       = (int)($item['quantity'] ?? 1);

        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        $stmtV = $pdo->prepare("
            SELECT pv.variant_id, pv.price, pv.stock_quantity, pv.frame_color, pv.lens_color, p.name AS product_name, p.sku_base
            FROM product_variants pv
            JOIN products p ON p.product_id = pv.product_id
            WHERE p.product_id = :pid AND pv.is_active = 1
            LIMIT 1
        ");
        $stmtV->execute(['pid' => $productId]);
        $varData = $stmtV->fetch();

        if (!$varData) {
            throw new Exception("Produk ID {$productId} tidak ditemukan atau tidak aktif.");
        }

        if ($varData['stock_quantity'] < $qty) {
            throw new Exception("Stok untuk produk '{$varData['product_name']}' tidak mencukupi (Tersedia: {$varData['stock_quantity']}).");
        }

        $unitPrice = (float)$varData['price'];
        $lineSubtotal = $unitPrice * $qty;
        $subtotalAmount += $lineSubtotal;

        $orderItemsPrepared[] = [
            'variant_id'    => $varData['variant_id'],
            'product_name'  => $varData['product_name'],
            'frame_color'   => $varData['frame_color'],
            'lens_color'    => $varData['lens_color'],
            'sku_variant'   => $varData['sku_base'] . '-VAR1',
            'unit_price'    => $unitPrice,
            'quantity'      => $qty,
            'line_subtotal' => $lineSubtotal
        ];
    }

    if (empty($orderItemsPrepared)) {
        throw new Exception("Tidak ada item produk valid yang dibeli.");
    }

    $totalAmount = $subtotalAmount + $shippingCost;

    $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    $userId = 1;

    $stmtO = $pdo->prepare("
        INSERT INTO orders (
            order_number, user_id, shipping_recipient_name, shipping_phone, 
            shipping_address_line1, shipping_city, shipping_province, shipping_postal_code, 
            shipping_country, subtotal_amount, shipping_cost, discount_amount, total_amount, 
            order_status, payment_status, shipping_status
        ) VALUES (
            :ord_num, :uid, :rec_name, :phone, 
            :addr, :city, :prov, :post, 
            'Indonesia', :subtotal, :ship_cost, 0, :total, 
            'pending', 'unpaid', 'not_shipped'
        )
    ");

    $stmtO->execute([
        'ord_num'   => $orderNumber,
        'uid'       => $userId,
        'rec_name'  => $customerName,
        'phone'     => $customerPhone,
        'addr'      => $addressLine,
        'city'      => $city,
        'prov'      => $province,
        'post'      => $postalCode,
        'subtotal'  => $subtotalAmount,
        'ship_cost' => $shippingCost,
        'total'     => $totalAmount
    ]);

    $orderId = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id, variant_id, product_name_snapshot, frame_color_snapshot, 
            lens_color_snapshot, lens_type_snapshot, sku_variant_snapshot, unit_price, quantity, line_subtotal
        ) VALUES (
            :oid, :vid, :pname, :fc, 
            :lc, 'UV400', :sku, :price, :qty, :line_sub
        )
    ");

    $stmtDecStock = $pdo->prepare("
        UPDATE product_variants 
        SET stock_quantity = stock_quantity - :qty 
        WHERE variant_id = :vid
    ");

    foreach ($orderItemsPrepared as $item) {
        $stmtItem->execute([
            'oid'      => $orderId,
            'vid'      => $item['variant_id'],
            'pname'    => $item['product_name'],
            'fc'       => $item['frame_color'],
            'lc'       => $item['lens_color'],
            'sku'      => $item['sku_variant'],
            'price'    => $item['unit_price'],
            'qty'      => $item['quantity'],
            'line_sub' => $item['line_subtotal']
        ]);

        $stmtDecStock->execute([
            'qty' => $item['quantity'],
            'vid' => $item['variant_id']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Pesanan berhasil dibuat!',
        'order_id'     => (int)$orderId,
        'order_number' => $orderNumber,
        'total_amount' => $totalAmount
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage()
    ]);
}