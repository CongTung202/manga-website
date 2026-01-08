<?php
require_once 'includes/db.php';

$articleId = $_GET['id'] ?? null;
$chapterId = $_GET['chap'] ?? null;
if (!$chapterId) die("Lỗi: Không tìm thấy chapter.");

// 1. Tăng View (Có kiểm tra Session)
// --- [LOGIC MỚI] ---
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['viewed_chapters'])) {
    $_SESSION['viewed_chapters'] = [];
}

// Nếu ChapterID chưa có trong danh sách đã xem -> Tăng view
if (!in_array($chapterId, $_SESSION['viewed_chapters'])) {
    $pdo->prepare("UPDATE chapters SET ViewCount = ViewCount + 1 WHERE ChapterID = ?")->execute([$chapterId]);
    $_SESSION['viewed_chapters'][] = $chapterId; // Đánh dấu đã xem
}
// -------------------

// 2. Lấy thông tin Chapter & Truyện
$stmt = $pdo->prepare("
    SELECT c.*, a.Title as ArticleTitle 
    FROM chapters c 
    JOIN articles a ON c.ArticleID = a.ArticleID 
    WHERE c.ChapterID = ?
");
$stmt->execute([$chapterId]);
$chapter = $stmt->fetch();

if (!$chapter) die("Chapter không tồn tại.");

// 3. Lấy ảnh
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

// Gọi Header (Lúc này header KHÔNG còn mở main-container nữa nên layout sẽ không bị chia đôi)
require_once 'includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/chapter.css">

<style>
    /* Đảm bảo nền đen bao phủ toàn bộ */
    body { background-color: var(--bg-body); overflow-x: hidden; }
    
    /* Container bình luận riêng cho trang đọc */
    .reader-comments {
        max-width: var(--viewer-width); /* 690px */
        margin: 0 auto;
        padding-top: 30px;
        padding-bottom: 50px;
        border-top: 1px solid var(--border-color);
    }
</style>

<div class="viewer-toolbar">
    <div class="toolbar-left">
        <a href="detail.php?id=<?= $articleId ?>" class="btn-back">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        
        <h1 class="chapter-title">  
            <a href="detail.php?id=<?= $articleId ?>" class="btn-home">
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
        <?php if ($isBookmarked): ?>
            <button class="btn-toolbar btn-interest" onclick="window.location.href='includes/action_bookmark.php?id=<?= $articleId ?>&redirect=read&chap=<?= $chapterId ?>'">
                <i class="fa-solid fa-check-circle"></i> Đã theo dõi
            </button>
        <?php else: ?>
            <button class="btn-toolbar btn-interest" onclick="window.location.href='includes/action_bookmark.php?id=<?= $articleId ?>&redirect=read&chap=<?= $chapterId ?>'">
                <i class="fa-solid fa-circle-plus"></i> Theo dõi
            </button>
        <?php endif; ?>

        <span class="divider">|</span>
        <a href="detail.php?id=<?= $articleId ?>#chapter-list" class="btn-toolbar">
            <i class="fa-solid fa-list-ul"></i> DS chương
        </a>
        <span class="divider">|</span>
        
        <a href="<?= $nextChap ? "read.php?id=$articleId&chap={$nextChap['ChapterID']}" : '#' ?>" 
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
        <p style="margin-bottom: 20px;">Hết Chapter <?= $chapter['Index'] ?></p>
        
        <div style="display: flex; justify-content: center; gap: 15px;">
            <a href="<?= $prevChap ? "read.php?id=$articleId&chap={$prevChap['ChapterID']}" : '#' ?>" 
               class="btn-nav-round <?= !$prevChap ? 'disabled' : '' ?>" 
               style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-main);">
               <i class="fa-solid fa-chevron-left"></i>
            </a>

            <a href="detail.php?id=<?= $articleId ?>" 
               style="padding: 8px 30px; border-radius: 20px; border: 1px solid var(--border-color); color: var(--text-main); font-weight: bold; background: var(--bg-element);">
               Danh sách
            </a>

            <a href="<?= $nextChap ? "read.php?id=$articleId&chap={$nextChap['ChapterID']}" : '#' ?>" 
               class="btn-nav-round <?= !$nextChap ? 'disabled' : '' ?>" 
               style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-main);">
               <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </div>
    
    <div class="reader-comments" id="comments">
        <?php require_once 'includes/comment_section.php'; ?>
    </div>

</main>

<?php 
// Không gọi footer.php ở đây vì giao diện Reader thường tối giản, 
// nhưng nếu muốn bạn có thể uncomment dòng dưới
// require_once 'includes/footer.php'; 
?>
</body>
</html>