<?php
// backend/api/settings/index.php

require_once '../../config/cors.php';
require_once '../../config/database.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    // Ambil data settings dari database
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $rawSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Default Fallback jika database masih kosong
    $response = [
        'store_name'       => $rawSettings['store_name'] ?? 'IDVISION Eyewear',
        'whatsapp_number'  => $rawSettings['whatsapp_number'] ?? '6281234567890',
        'announcement_bar' => $rawSettings['announcement_bar'] ?? 'Gratis Ongkir Seluruh Indonesia untuk Pembelian Kacamata Polarized!',
        'store_address'    => $rawSettings['store_address'] ?? 'Jl. Raya Utama No. 45, Jakarta',
        'instagram_url'    => $rawSettings['instagram_url'] ?? 'https://instagram.com/idvision.eyewear',
        'email_contact'    => $rawSettings['email_contact'] ?? 'support@idvision.com'
    ];

    echo json_encode([
        'success' => true,
        'data'    => $response
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage()
    ]);
}