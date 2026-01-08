<?php
// BẮT BUỘC: Khởi động session đầu tiên
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1';
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

if (!defined('BASE_URL')) define('BASE_URL', '/manga-website/');
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__)); 
?>