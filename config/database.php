<?php
// backend/config/database.php

function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            // Kredensial Supabase
            $host     = getenv('DB_HOST') ?: 'db.anaulwirhpttgpxyezyd.supabase.co';
            $port     = getenv('DB_PORT') ?: '5432';
            $dbname   = getenv('DB_DATABASE') ?: 'postgres';
            $user     = getenv('DB_USERNAME') ?: 'postgres';
            $password = getenv('DB_PASSWORD') ?: 'idvisionstore123';

            // DSN pgsql dengan penanganan khusus Supabase
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