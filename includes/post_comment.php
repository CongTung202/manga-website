<?php
require_once 'db.php'; // Kết nối CSDL và start session

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die("Bạn cần đăng nhập để bình luận.");
}

// 2. Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    $articleId = $_POST['article_id'] ?? null;
    $chapterId = $_POST['chapter_id'] ?? null; // Có thể null nếu bình luận cho truyện
    $content = trim($_POST['content']);
    $redirectUrl = $_POST['redirect_url'] ?? '../index.php';

    // Validate
    if (!$articleId || empty($content)) {
        // Có thể xử lý lỗi kỹ hơn, tạm thời quay lại trang cũ
        header("Location: " . $redirectUrl);
        exit;
    }

    // 3. Insert vào Database
    // Lưu ý: ChapterID có thể là NULL, nên ta cần xử lý câu query linh hoạt
    try {
        if ($chapterId) {
            $sql = "INSERT INTO comments (UserID, ArticleID, ChapterID, Content, CreatedAt) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $articleId, $chapterId, $content]);
        } else {
            $sql = "INSERT INTO comments (UserID, ArticleID, Content, CreatedAt) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $articleId, $content]);
        }
    } catch (Exception $e) {
        // Ghi log lỗi nếu cần
    }

    // 4. Quay lại trang cũ
    header("Location: " . $redirectUrl);
    exit;
}
?>