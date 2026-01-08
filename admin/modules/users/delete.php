<?php
require_once '../../../includes/db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // Soft Delete: Chỉ ẩn user đi, không xóa khỏi DB để giữ lại bình luận/lịch sử của họ
    $stmt = $pdo->prepare("UPDATE users SET IsDeleted = 1 WHERE UserID = ?");
    $stmt->execute([$id]);
}

header("Location: index.php");
exit;
?>