<?php
// GỌI FILE CẤU HÌNH CHÍNH (config.php)
require_once __DIR__ . '/config.php';

// BẮT BUỘC: Khởi động session đầu tiên
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CẤU HÌNH CƠ SỞ DỮ LIỆU
$host = 'localhost';
$db   = 'mangawebsite';
$user = 'root';
$pass = '123456'; // Password của XAMPP thường là rỗng
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
