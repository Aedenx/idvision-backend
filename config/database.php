<?php
// backend/config/database.php

function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $host     = '127.0.0.1';
            $port     = '3306';
            $dbname   = 'ecommerce_kacamata'; // Sesuaikan dengan nama database MySQL lokal kamu
            $user     = 'root';
            $password = '';

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

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