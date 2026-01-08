<?php
require_once 'includes/db.php';

// 1. Lấy danh sách tất cả thể loại
$stmtGenres = $pdo->query("SELECT * FROM genres ORDER BY Name ASC");
$allGenres = $stmtGenres->fetchAll();

// 2. Xử lý Lọc truyện
$currentGenreId = $_GET['genre_id'] ?? 0; // 0 là tất cả
$pageTitle = "Thể loại";

if ($currentGenreId > 0) {
    // Lấy tên thể loại đang chọn
    foreach($allGenres as $g) {
        if ($g['GenreID'] == $currentGenreId) {
            $pageTitle = $g['Name'];
            break;
        }
    }

    // Query truyện theo thể loại (JOIN bảng trung gian)
    $sql = "SELECT a.* FROM articles a
            JOIN articles_genres ag ON a.ArticleID = ag.ArticleID
            WHERE ag.GenreID = ? AND a.IsDeleted = 0
            ORDER BY a.UpdatedAt DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentGenreId]);
} else {
    // Nếu không chọn -> Lấy tất cả
    $pageTitle = "Tất cả thể loại";
    $stmt = $pdo->query("SELECT * FROM articles WHERE IsDeleted = 0 ORDER BY UpdatedAt DESC");
}

$articles = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<style>
    /* CSS Riêng cho trang Thể loại (Dark Mode) */
    .genre-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }
    
    /* Style nút thể loại dạng thẻ */
    .btn-genre {
        padding: 6px 16px;
        border-radius: 20px;
        border: 1px solid var(--border-color);
        background-color: var(--bg-element);
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn-genre:hover {
        border-color: var(--primary-theme);
        color: var(--text-main);
    }
    
    /* Trạng thái đang chọn */
    .btn-genre.active {
        background-color: var(--primary-theme);
        color: #fff;
        border-color: var(--primary-theme);
        font-weight: bold;
    }
</style>

<div class="main-container">
    <main class="content">
        
        <section class="section">
            <div class="section__header">
                <h3>Tìm theo thể loại</h3>
            </div>

            <div class="genre-nav">
                <a href="genres.php" class="btn-genre <?= $currentGenreId == 0 ? 'active' : '' ?>">
                    Tất cả
                </a>
                
                <?php foreach($allGenres as $g): ?>
                    <a href="genres.php?genre_id=<?= $g['GenreID'] ?>" 
                       class="btn-genre <?= $currentGenreId == $g['GenreID'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($g['Name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="section__header">
                <h3><?= htmlspecialchars($pageTitle) ?></h3>
                <span style="font-size: 12px; color: var(--text-muted); margin-left: auto;">
                    Tìm thấy <?= count($articles) ?> truyện
                </span>
            </div>

            <?php if (count($articles) > 0): ?>
                <div class="card-list">
                    <?php foreach($articles as $art): ?>
                    <article class="card" onclick="window.location.href='detail.php?id=<?= $art['ArticleID'] ?>'">
                        <div class="card__thumb">
                            <?php if($art['CoverImage']): ?>
                                <img src="<?= getImageUrl($art['CoverImage']) ?>" alt="<?= htmlspecialchars($art['Title']) ?>">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:#333; display:flex; align-items:center; justify-content:center; color:#777;">No Image</div>
                            <?php endif; ?>
                        </div>
                        <h4 class="card__title"><?= htmlspecialchars($art['Title']) ?></h4>
                        <p class="card__author" style="font-size: 11px; color: var(--text-muted);">
                            <i class="fas fa-eye me-1"></i> <?= number_format($art['ViewCount']) ?>
                        </p>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-muted);">
                    <i class="far fa-sad-tear" style="font-size: 40px; margin-bottom: 15px;"></i>
                    <p>Chưa có truyện nào thuộc thể loại này.</p>
                    <a href="genres.php" style="color: var(--primary-theme); font-weight: bold;">Xem tất cả thể loại</a>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>
</div>

<?php require_once 'includes/footer.php'; ?>