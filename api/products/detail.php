<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/database.php';

$pdo = getDbConnection();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    echo json_encode(['success' => false, 'message' => 'Slug tidak ditemukan']);
    exit;
}

// 1. Ambil Data Produk Utama
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.slug = :slug AND p.is_active = 1
    LIMIT 1
");
$stmt->execute(['slug' => $slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit;
}

// 2. Ambil Gambar-Gambar Produk
$stmtImages = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :id ORDER BY is_primary DESC");
$stmtImages->execute(['id' => $product['product_id']]);
$images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

// 3. Ambil Varian Produk (Warna & Stok)
$stmtVariants = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = :id");
$stmtVariants->execute(['id' => $product['product_id']]);
$variants = $stmtVariants->fetchAll(PDO::FETCH_ASSOC);

$product['images'] = $images;
$product['variants'] = $variants;

echo json_encode([
    'success' => true,
    'data' => $product
]);