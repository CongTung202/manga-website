<?php
// Đường dẫn: admin/modules/chapters/delete.php
require_once '../../../includes/db.php';

$id = $_GET['id'] ?? null;
$articleId = $_GET['article_id'] ?? null; 

if ($id) {
    // 1. Xóa mềm Chapter (Cập nhật IsDeleted = 1)
    $stmt = $pdo->prepare("UPDATE chapters SET IsDeleted = 1 WHERE ChapterID = ?");
    $stmt->execute([$id]);
}

// Quay lại trang chi tiết truyện
if ($articleId) {
    header("Location: ../articles/view.php?id=" . $articleId);
} else {
    header("Location: ../articles/index.php");
}
exit;
?>