<?php
// includes/search_ajax.php
require_once 'db.php';

// Lấy từ khóa từ request
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // Tìm kiếm tương đối (LIKE)
    // MySQL collation utf8_general_ci mặc định không phân biệt dấu/hoa thường
    $stmt = $pdo->prepare("SELECT ArticleID, Title, CoverImage, Slug 
                           FROM articles 
                           WHERE Title LIKE ? AND IsDeleted = 0 
                           LIMIT 5"); // Giới hạn 5 kết quả để hiển thị đẹp
    $stmt->execute(["%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Trả về JSON
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>