<?php
require_once 'includes/db.php';

// 1. Lấy danh sách Categories
$allCats = $pdo->query("SELECT * FROM categories ORDER BY Name ASC")->fetchAll();

// 2. Xử lý Lọc
$currentCatId = $_GET['cat_id'] ?? 0;
$pageTitle = "Phân loại truyện";

if ($currentCatId > 0) {
    // Lấy tên
    foreach($allCats as $c) {
        if ($c['CategoryID'] == $currentCatId) {
            $pageTitle = $c['Name'];
            break;
        }
    }
    // Query truyện theo CategoryID
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE CategoryID = ? AND IsDeleted = 0 ORDER BY UpdatedAt DESC");
    $stmt->execute([$currentCatId]);
} else {
    $pageTitle = "Tất cả phân loại";
    $stmt = $pdo->query("SELECT * FROM articles WHERE IsDeleted = 0 ORDER BY UpdatedAt DESC");
}

$articles = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<style>
    /* CSS Riêng cho trang Phân loại (Dark Mode) */
    
    /* Tab Navigation Style */
    .type-nav { 
        display: flex; 
        justify-content: center; 
        gap: 20px; 
        margin-bottom: 40px; 
        border-bottom: 2px solid var(--border-color); 
    }
    
    .nav-type-item {
        padding: 15px 10px;
        font-weight: bold;
        color: var(--text-muted);
        font-size: 15px;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px; /* Đè lên line border bottom */
        transition: 0.2s;
        text-decoration: none;
        text-transform: uppercase;
    }
    
    .nav-type-item:hover { 
        color: var(--text-main); 
    }
    
    .nav-type-item.active {
        color: var(--primary-theme);
        border-bottom-color: var(--primary-theme);
    }
</style>
<div class="main-container">
    <main class="content">
        
        <div class="type-nav">
            <a href="types.php" class="nav-type-item <?= $currentCatId == 0 ? 'active' : '' ?>">
                Tất cả
            </a>
            <?php foreach($allCats as $c): ?>
                <a href="types.php?cat_id=<?= $c['CategoryID'] ?>" 
                   class="nav-type-item <?= $currentCatId == $c['CategoryID'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['Name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <section class="section">
            <div class="section__header">
                <h3><?= htmlspecialchars($pageTitle) ?></h3>
                <span style="font-size: 12px; color: var(--text-muted); margin-left: auto;">
                    <?= count($articles) ?> kết quả
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
                                <div style="width:100%; height:100%; background:#333; display:flex; align-items:center; justify-content:center; color:#777;">No Img</div>
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
                    <i class="far fa-folder-open" style="font-size: 40px; margin-bottom: 15px;"></i>
                    <p>Chưa có truyện nào thuộc phân loại này.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>
</div>

<?php require_once 'includes/footer.php'; ?>