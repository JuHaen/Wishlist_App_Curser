<?php
// Database connection helper using PDO
function getPDO(): PDO
{
    if (!file_exists(__DIR__ . '/config.php')) {
        die('Application is not installed. Please run install.php');
    }

    $config = include __DIR__ . '/config.php';

    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], $options);
    }

    return $pdo;
}
