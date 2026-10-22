<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../../config/database.php';

$pdo = getDbConnection();

try {
    $stmt = $pdo->query("SELECT category_id, name, slug, description, image_path, is_active FROM categories WHERE is_active = 1 ORDER BY category_id ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/idvision/backend/uploads/categories/";

    foreach ($categories as &$cat) {
        // Buat slug otomatis jika di database masih kosong
        if (empty($cat['slug'])) {
            $cat['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat['name']), '-'));
        }
        $cat['image_url'] = !empty($cat['image_path']) ? $baseUrl . $cat['image_path'] : null;
    }

    echo json_encode([
        "status" => "success",
        "data"   => $categories
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}