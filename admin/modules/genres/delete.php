<?php
require_once '../../../includes/db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // 1. Xóa liên kết trong bảng articles_genres trước (Nếu DB không có ON DELETE CASCADE)
    $pdo->prepare("DELETE FROM articles_genres WHERE GenreID = ?")->execute([$id]);
    
    // 2. Xóa thể loại
    $pdo->prepare("DELETE FROM genres WHERE GenreID = ?")->execute([$id]);
}

header("Location: index.php");
exit;
?>