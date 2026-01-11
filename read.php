<?php
require_once 'includes/db.php';
require_once 'includes/functions.php'; // Gọi hàm để dùng getImageUrl

$articleId = $_GET['id'] ?? null;
$chapterId = $_GET['chap'] ?? null;
if (!$chapterId) die("Lỗi: Không tìm thấy chapter.");

// 1. Tăng View
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['viewed_chapters'])) $_SESSION['viewed_chapters'] = [];
if (!in_array($chapterId, $_SESSION['viewed_chapters'])) {
    $pdo->prepare("UPDATE chapters SET ViewCount = ViewCount + 1 WHERE ChapterID = ?")->execute([$chapterId]);
    $_SESSION['viewed_chapters'][] = $chapterId; 
}

// 2. Lấy thông tin Chapter & Truyện
// [QUAN TRỌNG] Thêm a.CoverImage vào SELECT để lấy ảnh bìa lưu vào lịch sử
$stmt = $pdo->prepare("
    SELECT c.*, a.Title as ArticleTitle, a.CoverImage 
    FROM chapters c 
    JOIN articles a ON c.ArticleID = a.ArticleID 
    WHERE c.ChapterID = ?
");
$stmt->execute([$chapterId]);
$chapter = $stmt->fetch();

if (!$chapter) die("Chapter không tồn tại.");

// --- [CODE CŨ] LƯU LỊCH SỬ ĐỌC VÀO COOKIE (Cho khách & Backup) ---
$cookieName = 'manga_history';
$history = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : [];

// Tạo mảng dữ liệu cho chương hiện tại
$newItem = [
    'id' => $articleId,
    'title' => $chapter['ArticleTitle'],
    'image' => $chapter['CoverImage'], // Lưu đường dẫn ảnh
    'chap_id' => $chapterId,
    'chap_index' => $chapter['Index'],
    'time' => time()
];

// 1. Xóa truyện này nếu đã có trong lịch sử (để đưa lên đầu)
$history = array_filter($history, function($item) use ($articleId) {
    return $item['id'] != $articleId;
});

// 2. Thêm vào đầu mảng
array_unshift($history, $newItem);

// 3. Giới hạn chỉ lưu 5 truyện gần nhất
$history = array_slice($history, 0, 5);

// 4. Lưu Cookie (Quan trọng: set path là "/" để nhận trên toàn domain)
setcookie($cookieName, json_encode($history), time() + (86400 * 30), "/");

// --- [CODE MỚI] LƯU DATABASE (Cho thành viên đã đăng nhập) ---
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Sử dụng ON DUPLICATE KEY UPDATE: 
    // Nếu User đã đọc truyện này rồi thì chỉ cập nhật ChapterID và thời gian mới nhất
    // (Dựa vào UNIQUE KEY `User_Article_Unique` trong Database)
    $sqlHistory = "INSERT INTO history (UserID, ArticleID, ChapterID, LastReadAt) 
                   VALUES (?, ?, ?, NOW()) 
                   ON DUPLICATE KEY UPDATE ChapterID = VALUES(ChapterID), LastReadAt = NOW()";
    
    $stmtHist = $pdo->prepare($sqlHistory);
    // Lưu ý: $articleId lấy từ $_GET, $chapterId lấy từ $_GET
    $stmtHist->execute([$userId, $articleId, $chapterId]);
}
// ---------------------------------------------

// 3. Lấy ảnh nội dung
$stmtImg = $pdo->prepare("SELECT * FROM chapter_images WHERE ChapterID = ? ORDER BY SortOrder ASC");
$stmtImg->execute([$chapterId]);
$images = $stmtImg->fetchAll();

// 4. Chapter Trước/Sau
$stmtPrev = $pdo->prepare("SELECT ChapterID FROM chapters WHERE ArticleID = ? AND `Index` < ? ORDER BY `Index` DESC LIMIT 1");
$stmtPrev->execute([$articleId, $chapter['Index']]);
$prevChap = $stmtPrev->fetch();

$stmtNext = $pdo->prepare("SELECT ChapterID FROM chapters WHERE ArticleID = ? AND `Index` > ? ORDER BY `Index` ASC LIMIT 1");
$stmtNext->execute([$articleId, $chapter['Index']]);
$nextChap = $stmtNext->fetch();

// 5. Kiểm tra Bookmark
$isBookmarked = false;
if (isset($_SESSION['user_id'])) {
    $stmtCheck = $pdo->prepare("SELECT BookmarkID FROM bookmarks WHERE UserID = ? AND ArticleID = ?");
    $stmtCheck->execute([$_SESSION['user_id'], $articleId]);
    if ($stmtCheck->rowCount() > 0) $isBookmarked = true;
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/chapter.css">

<style>
    /* Nền đen cho trang đọc */
    body { background-color: var(--bg-body); overflow-x: hidden; }
    
    /* Vùng chứa bình luận: Rộng bằng vùng ảnh truyện */
    .reader-comments-wrapper {
        max-width: 800px; /* Độ rộng vừa phải để dễ đọc */
        margin: 0 auto;
        padding: 40px 15px;
        background-color: var(--bg-element);
        border-radius: 8px;
        margin-top: 50px;
        margin-bottom: 50px;
        border: 1px solid var(--border-color);
    }
    
    .reader-comments-wrapper h3 {
        color: var(--text-main);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
</style>

<div class="viewer-toolbar">
    <div class="toolbar-left">
        <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>" class="btn-back">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        
        <h1 class="chapter-title">  
            <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>" class="btn-home">
                <i class="fa-solid fa-house me-1"></i>
                <span class="webtoon-name"><?= htmlspecialchars($chapter['ArticleTitle']) ?></span>
            </a>
            <span class="divider">|</span>
            <span class="episode-name">
                Chapter <?= $chapter['Index'] ?> 
                <?= !empty($chapter['Title']) ? ': ' . htmlspecialchars($chapter['Title']) : '' ?>
            </span>
        </h1>
    </div>

    <div class="toolbar-right">
        <button id="btn-follow" class="btn-toolbar btn-interest" data-id="<?= $articleId ?>">
            <?php if ($isBookmarked): ?>
                <i class="fa-solid fa-check-circle"></i> <span>Đã theo dõi</span>
            <?php else: ?>
                <i class="fa-solid fa-circle-plus"></i> <span>Theo dõi</span>
            <?php endif; ?>
        </button>

        <span class="divider">|</span>
        <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>#chapter-list" class="btn-toolbar">
            <i class="fa-solid fa-list-ul"></i> DS chương
        </a>
        <span class="divider">|</span>
        
        <a href="<?= $nextChap ? BASE_URL . 'doc/' . $articleId . '/' . $nextChap['ChapterID'] : '#' ?>" 
           class="btn-toolbar nav-arrow <?= !$nextChap ? 'disabled' : '' ?>">
            Sau <i class="fa-solid fa-caret-right ms-1"></i>
        </a>
    </div>
</div>

<main class="viewer-container">
    <div class="comic-strip">
        <?php foreach($images as $img): ?>
          <img src="<?= getImageUrl($img['ImageURL']) ?>" alt="Page" loading="lazy">
        <?php endforeach; ?>
        
        <?php if(count($images) == 0): ?>
            <div style="padding: 100px 0; text-align: center; color: var(--text-muted);">
                <i class="fa-regular fa-image" style="font-size: 40px; margin-bottom: 20px; display: block;"></i>
                <p>Chương này chưa có nội dung ảnh.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="viewer-footer">
        <p style="margin-bottom: 20px; color: var(--text-muted);">Hết Chapter <?= $chapter['Index'] ?></p>
        
        <div style="display: flex; justify-content: center; gap: 15px;">
            <a href="<?= $prevChap ? BASE_URL . 'doc/' . $articleId . '/' . $prevChap['ChapterID'] : '#' ?>" 
               class="btn-nav-round <?= !$prevChap ? 'disabled' : '' ?>" 
               style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-main);">
               <i class="fa-solid fa-chevron-left"></i>
            </a>

            <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>" 
               style="padding: 8px 30px; border-radius: 20px; border: 1px solid var(--border-color); color: var(--text-main); font-weight: bold; background: var(--bg-element);">
               Danh sách
            </a>

            <a href="<?= $nextChap ? BASE_URL . 'doc/' . $articleId . '/' . $nextChap['ChapterID'] : '#' ?>" 
               class="btn-nav-round <?= !$nextChap ? 'disabled' : '' ?>" 
               style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-main);">
               <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </div>
    
    <div class="reader-comments-wrapper" id="comment-section">
        <h3><i class="fas fa-comments me-2"></i>Bình luận</h3>
        <?php 
        // [SỬA LỖI] Gán biến $id để file comment_section.php hiểu được
        $id = $articleId; 
        
        // Tạo biến redirect để khi bình luận xong thì quay lại đúng trang đọc truyện này
        $currentUrl = "../read.php?id=$articleId&chap=$chapterId#comment-section";
        
        require_once 'includes/comment_section.php'; 
        ?>
    </div>

</main>

<script>
document.getElementById('btn-follow').addEventListener('click', function() {
    const btn = this;
    const articleId = btn.getAttribute('data-id');
    const icon = btn.querySelector('i');
    const text = btn.querySelector('span');

    // Gọi API (Backend)
    fetch('includes/action_bookmark.php?ajax=1&id=' + articleId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.is_bookmarked) {
                // Đã theo dõi
                icon.className = 'fa-solid fa-check-circle';
                text.innerText = 'Đã theo dõi';
            } else {
                // Hủy theo dõi
                icon.className = 'fa-solid fa-circle-plus';
                text.innerText = 'Theo dõi';
            }
        } else if (data.status === 'login_required') {
            alert("Vui lòng đăng nhập để theo dõi truyện!");
            window.location.href = 'login.php';
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>

</body>
</html>