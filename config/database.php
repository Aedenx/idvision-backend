<?php
// backend/config/database.php

function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
$host     = getenv('DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com';
$port     = getenv('DB_PORT') ?: '5432';
$dbname   = getenv('DB_NAME') ?: 'postgres';         // Ubah dari DB_DATABASE ke DB_NAME
$user     = getenv('DB_USER') ?: 'postgres.pikxqcmqxhfxeqanozix'; // Ubah dari DB_USERNAME dan sesuaikan user baru
$password = getenv('DB_PASSWORD') ?: 'idvisionstore123';

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database Connection Error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    return $pdo;
}