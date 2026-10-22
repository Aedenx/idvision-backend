<?php
// backend/index.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Tangkap request URI yang masuk
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Jika mengakses root / atau /api/products, arahkan langsung ke file API Products
if ($requestUri === '/' || str_contains($requestUri, 'products')) {
    $apiFile = __DIR__ . '/api/products/index.php';
    if (file_exists($apiFile)) {
        require_once $apiFile;
        exit();
    }
}

// Default response jika route tidak ditemukan
echo json_encode([
    'success' => true,
    'message' => 'API Backend IDVISION Active',
    'uri' => $requestUri
]);