<?php
require_once 'includes/db.php';
$pageTitle = "Trang chủ - GTSCHUNDER";
require_once 'includes/header.php';

// Hàm tính thời gian tương đối
function getRelativeTime($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return $diff . ' giây trước';
    else if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    else if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    else if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    else if ($diff < 2592000) return floor($diff / 604800) . ' tuần trước';
    else return floor($diff / 2592000) . ' tháng trước';
} 

// 1. Lấy truyện mới cập nhật (Ẩn CategoryID = 4)
$stmtNew = $pdo->query("
    SELECT a.*, 
           (SELECT c.`Index` FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterIndex,
           (SELECT c.Title FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterTitle,
           (SELECT c.CreatedAt FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterDate
    FROM articles a 
    WHERE a.IsDeleted = 0 AND (a.CategoryID != 4 OR a.CategoryID IS NULL)
    ORDER BY a.UpdatedAt DESC 
    LIMIT 10
");
$articlesNew = $stmtNew->fetchAll();

// 2. Lấy truyện nhiều lượt xem - Top 5 (Ẩn CategoryID = 4)
$stmtTop = $pdo->query("
    SELECT a.*, 
           (SELECT c.`Index` FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterIndex,
           (SELECT c.Title FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterTitle,
           (SELECT c.CreatedAt FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterDate
    FROM articles a 
    WHERE a.IsDeleted = 0 AND (a.CategoryID != 4 OR a.CategoryID IS NULL)
    GROUP BY a.ArticleID
    ORDER BY a.ViewCount DESC 
    LIMIT 5
");
$articlesTop = $stmtTop->fetchAll();
?>
<div class="main-container">
<main class="content">
    
    <section class="section">
        <div class="section__header">
            <h3>Truyện mới đăng</h3>
            <a href="<?= BASE_URL ?>/types" class="section__view-all">Xem tất cả ></a>
        </div>

        <div class="card-list">
            <?php foreach($articlesNew as $art): ?>
            <article class="card" onclick="window.location.href='<?= BASE_URL ?>truyen/<?= $art['ArticleID'] ?>'">
                <div class="card__thumb">
                    <?php if($art['CoverImage']): ?>
                        <img src="<?= getImageUrl($art['CoverImage']) ?>" alt="<?= htmlspecialchars($art['Title']) ?>">
                    <?php else: ?>
                        <div style="width:100%; height:100%; background:#333; display:flex; align-items:center; justify-content:center; color:#777;">No Image</div>
                    <?php endif; ?>
                    <span class="badge-up">UP</span>
                </div>
                <h4 class="card__title"><?= htmlspecialchars($art['Title']) ?></h4>
                <p class="card__chapter">
                    <?php 
                    if (!empty($art['LatestChapterDate'])) {
                        $chapterIndex = !empty($art['LatestChapterIndex']) ? $art['LatestChapterIndex'] : '?';
                        $chapterTitle = !empty($art['LatestChapterTitle']) ? htmlspecialchars($art['LatestChapterTitle']) : 'N/A';
                        $relativeTime = getRelativeTime($art['LatestChapterDate']);
                        echo '<i class="fas fa-book-open"></i> Chương ' . $chapterIndex . ' - '. $relativeTime;
                    } else {
                        echo '<i class="fas fa-book-open"></i> Chưa có chương';
                    }
                    ?>
                </p>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <hr class="divider">

    <section class="section">
        <div class="section__header">
            <h3>Truyện nhiều lượt xem nhất</h3>
            <a href="<?= BASE_URL ?>/genres" class="section__view-all">Xem tất cả ></a>
        </div>

        <div class="card-list">
            <?php $rank = 1; foreach($articlesTop as $art): ?>
            <article class="card" onclick="window.location.href='<?= BASE_URL ?>truyen/<?= $art['ArticleID'] ?>'">
                <div class="card__thumb">
                    <?php if($art['CoverImage']): ?>
                        <img src="<?= getImageUrl($art['CoverImage']) ?>" alt="<?= htmlspecialchars($art['Title']) ?>">
                    <?php else: ?>
                        <div style="width:100%; height:100%; background:#333;"></div>
                    <?php endif; ?>
                    <span class="rank-number"><?= $rank ?></span>
                </div>
                <h4 class="card__title"><?= htmlspecialchars($art['Title']) ?></h4>
                <p class="card__chapter">
                    <?php 
                    if (!empty($art['LatestChapterDate'])) {
                        $chapterIndex = !empty($art['LatestChapterIndex']) ? $art['LatestChapterIndex'] : '?';
                        $relativeTime = getRelativeTime($art['LatestChapterDate']);
                        echo '<i class="fas fa-book-open"></i> Chương ' . $chapterIndex . ' - ' . $relativeTime;
                    } else {
                        echo '<i class="fas fa-book-open"></i> Chưa có chương';
                    }
                    ?>
                </p>
            </article>
            <?php $rank++; endforeach; ?>
        </div>
    </section>

</main>

<aside class="sidebar">
    <?php include 'includes/right_sidebar.php'; ?>
</aside>

<?php require_once 'includes/footer.php'; ?>