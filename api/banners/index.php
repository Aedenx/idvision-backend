<?php
// backend/api/banners/index.php

require_once '../../config/cors.php';
require_once '../../config/database.php';

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare("
        SELECT id, title, subtitle, image, cta_label, cta_link, sort_order, is_active 
        FROM banners 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    $stmt->execute();
    $banners = $stmt->fetchAll();

    $result = array_map(function($b) {
        return [
            'id'         => (int)$b['id'],
            'title'      => $b['title'],
            'subtitle'   => $b['subtitle'],
            'image'      => $b['image'],
            'cta_label'  => $b['cta_label'],
            'cta_link'   => $b['cta_link'],
            'sort_order' => (int)$b['sort_order'],
            'is_active'  => (bool)$b['is_active']
        ];
    }, $banners);

    echo json_encode($result);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}