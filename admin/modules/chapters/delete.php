<?php
// Đường dẫn: admin/modules/chapters/delete.php
require_once '../../../includes/db.php';

$id = $_GET['id'] ?? null;
$articleId = $_GET['article_id'] ?? null; 

if ($id) {
    try {
        // Bắt đầu Transaction để đảm bảo an toàn dữ liệu
        $pdo->beginTransaction();

        // 1. Xóa tất cả dữ liệu ảnh liên kết với Chapter này
        $stmtImg = $pdo->prepare("DELETE FROM chapter_images WHERE ChapterID = ?");
        $stmtImg->execute([$id]);

        // 2. Xóa lịch sử đọc của User liên quan đến Chapter này (Tránh lỗi khóa ngoại)
        $stmtHist = $pdo->prepare("DELETE FROM history WHERE ChapterID = ?");
        $stmtHist->execute([$id]);

        // 3. Xóa hoàn toàn (Hard Delete) Chapter
        $stmtChap = $pdo->prepare("DELETE FROM chapters WHERE ChapterID = ?");
        $stmtChap->execute([$id]);

        // Xác nhận thay đổi
        $pdo->commit();

    } catch (PDOException $e) {
        // Nếu có bất kỳ lỗi nào xảy ra, hoàn tác lại toàn bộ quá trình
        $pdo->rollBack();
        
        // Bạn có thể comment dòng die() này lại và thay bằng thông báo lỗi thân thiện hơn nếu muốn
        die("Lỗi cơ sở dữ liệu khi xóa: " . $e->getMessage());
    }
}

// Quay lại trang chi tiết truyện
if ($articleId) {
    header("Location: ../articles/view.php?id=" . $articleId);
} else {
    header("Location: ../articles/index.php");
}
exit;
?>