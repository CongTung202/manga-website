<?php
require_once 'db.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    // Nếu chưa đăng nhập, chuyển hướng sang trang login
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$articleId = $_GET['id'] ?? null;

if ($articleId) {
    // 2. Kiểm tra xem đã lưu chưa
    $stmtCheck = $pdo->prepare("SELECT BookmarkID FROM bookmarks WHERE UserID = ? AND ArticleID = ?");
    $stmtCheck->execute([$userId, $articleId]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        // 3a. Nếu ĐÃ có -> Xóa (Bỏ lưu)
        $stmtDel = $pdo->prepare("DELETE FROM bookmarks WHERE UserID = ? AND ArticleID = ?");
        $stmtDel->execute([$userId, $articleId]);
    } else {
        // 3b. Nếu CHƯA có -> Thêm mới (Lưu)
        $stmtAdd = $pdo->prepare("INSERT INTO bookmarks (UserID, ArticleID) VALUES (?, ?)");
        $stmtAdd->execute([$userId, $articleId]);
    }
}

// 4. Quay lại trang chi tiết truyện
header("Location: ../detail.php?id=" . $articleId);
exit;
?>