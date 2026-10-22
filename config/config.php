<?php
// backend/config/config.php

if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        define('BASE_URL', $protocol . $host . '/idvision');
    } else {
        define('BASE_URL', $protocol . $host);
    }

    define('ADMIN_LOGIN_URL', BASE_URL . '/admin/auth/login.php');
}