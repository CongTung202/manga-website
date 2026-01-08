<?php
// Sửa đường dẫn config.php thành db.php
require_once '../../../includes/db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // Thực hiện Soft Delete
    $stmt = $pdo->prepare("UPDATE articles SET IsDeleted = 1 WHERE ArticleID = ?");
    $stmt->execute([$id]);
}

header("Location: index.php");
exit;
?>