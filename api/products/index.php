<?php
// Header CORS agar Next.js bebas mengakses API
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$pdo = getDbConnection();

// Tangkap parameter query
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Base URL gambar otomatis mengikuti domain server (Back4app/Lokal)
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = getenv('NEXT_PUBLIC_STORAGE_URL') ?: "$scheme://$host/uploads/products/";

try {
    if (!empty($slug) || !empty($id)) {
        // --- 1. GET SINGLE PRODUCT DETAIL BY SLUG OR ID ---
        
        if (!empty($slug)) {
            $stmt = $pdo->prepare("
                SELECT 
                    p.product_id, 
                    p.name, 
                    p.slug, 
                    p.sku_base, 
                    p.description, 
                    p.base_price, 
                    p.discount_percent,
                    p.discount_price,
                    p.is_active, 
                    p.is_featured, 
                    p.created_at,
                    c.category_id, 
                    c.name AS category_name, 
                    c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE LOWER(p.slug) = LOWER(:slug_param)
                LIMIT 1
            ");
            $stmt->execute(['slug_param' => $slug]);
        } else {
            $stmt = $pdo->prepare("
                SELECT 
                    p.product_id, 
                    p.name, 
                    p.slug, 
                    p.sku_base, 
                    p.description, 
                    p.base_price, 
                    p.discount_percent,
                    p.discount_price,
                    p.is_active, 
                    p.is_featured, 
                    p.created_at,
                    c.category_id, 
                    c.name AS category_name, 
                    c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.product_id = :id_param
                LIMIT 1
            ");
            $stmt->execute(['id_param' => $id]);
        }

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            echo json_encode([
                "status"  => "error",
                "message" => "Produk tidak ditemukan"
            ], JSON_PRETTY_PRINT);
            exit;
        }

        // Query Varian Produk
        $stmtVars = $pdo->prepare("
            SELECT variant_id, sku_variant, color_name, price, stock_quantity, is_active
            FROM product_variants
            WHERE product_id = :pid
        ");
        $stmtVars->execute(['pid' => $product['product_id']]);
        $product['variants'] = $stmtVars->fetchAll(PDO::FETCH_ASSOC);

        // Query Foto Utama Produk
        $stmtImg = $pdo->prepare("
            SELECT image_path 
            FROM product_images 
            WHERE product_id = :pid AND is_primary = 1 
            LIMIT 1
        ");
        $stmtImg->execute(['pid' => $product['product_id']]);
        $img = $stmtImg->fetch(PDO::FETCH_ASSOC);

        $product['image_url'] = ($img && !empty($img['image_path'])) ? rtrim($baseUrl, '/') . '/' . ltrim($img['image_path'], '/') : null;

        echo json_encode([
            "status" => "success",
            "data"   => $product
        ], JSON_PRETTY_PRINT);

    } else {
        // --- 2. GET ALL PRODUCTS LIST WITH CATEGORY FILTER & REVIEWS ---
        
        $catInput    = isset($_GET['category']) && $_GET['category'] !== '' ? trim($_GET['category']) : (isset($_GET['gender']) && $_GET['gender'] !== '' ? trim($_GET['gender']) : null);
        $searchParam = isset($_GET['search']) ? trim($_GET['search']) : null;
        $sort        = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';
        
        $params = [];
        $sqlWhere = "WHERE p.is_active = 1";

        // Filter Kategori
        if (!empty($catInput) && strtolower($catInput) !== 'all') {
            $sqlWhere .= " AND (LOWER(c.slug) = LOWER(:cat_slug) OR LOWER(c.name) = LOWER(:cat_name))";
            $params['cat_slug'] = $catInput;
            $params['cat_name'] = $catInput;
        }

        // Filter Pencarian Keyword
        if (!empty($searchParam)) {
            $sqlWhere .= " AND (LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search))";
            $params['search'] = '%' . $searchParam . '%';
        }

        // Logika Pengurutan
        $orderBy = "ORDER BY p.product_id DESC";
        if ($sort === 'price_asc') {
            $orderBy = "ORDER BY COALESCE(p.discount_price, p.base_price) ASC";
        } elseif ($sort === 'price_desc') {
            $orderBy = "ORDER BY COALESCE(p.discount_price, p.base_price) DESC";
        } elseif ($sort === 'discount_desc') {
            $orderBy = "ORDER BY p.discount_percent DESC, p.product_id DESC";
        }

        $stmt = $pdo->prepare("
            SELECT 
                p.product_id, 
                p.name, 
                p.slug, 
                p.sku_base, 
                p.description, 
                p.base_price, 
                p.discount_percent,
                p.discount_price,
                p.is_featured,
                p.is_active,
                c.category_id,
                c.name AS category_name, 
                c.slug AS category_slug,
                COALESCE(SUM(pv.stock_quantity), 0) AS total_stock,
                (
                    SELECT pi.image_path 
                    FROM product_images pi 
                    WHERE pi.product_id = p.product_id AND pi.is_primary = 1 
                    LIMIT 1
                ) AS image_path,
                COALESCE(ROUND(AVG(r.rating), 1), 0) AS average_rating,
                COUNT(DISTINCT r.review_id) AS total_reviews
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN product_variants pv ON p.product_id = pv.product_id
            LEFT JOIN reviews r ON p.product_id = r.product_id
            $sqlWhere
            GROUP BY 
                p.product_id, p.name, p.slug, p.sku_base, p.description, 
                p.base_price, p.discount_percent, p.discount_price, p.is_featured, p.is_active, 
                c.category_id, c.name, c.slug
            $orderBy
        ");
        
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$p) {
            $p['image_url'] = !empty($p['image_path']) ? rtrim($baseUrl, '/') . '/' . ltrim($p['image_path'], '/') : null;
            $p['average_rating'] = floatval($p['average_rating']);
            $p['total_reviews']  = intval($p['total_reviews']);
        }

        echo json_encode([
            "status" => "success",
            "total"  => count($products),
            "data"   => $products
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error", 
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}