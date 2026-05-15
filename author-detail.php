<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Chi Tiết Tác Giả - GTSCHUNDER";
require_once 'includes/header.php';

// Hàm tính thời gian
function getRelativeTime($datetime) {
    $time = strtotime($datetime); $now = time(); $diff = $now - $time;
    if ($diff < 60) return $diff . ' giây trước';
    else if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    else if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    else if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    else if ($diff < 2592000) return floor($diff / 604800) . ' tuần trước';
    else return floor($diff / 2592000) . ' tháng trước';
}

$authorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($authorId <= 0) { header('Location: authors.php'); exit; }

$stmtAuthor = $pdo->prepare("SELECT * FROM authors WHERE AuthorID = ? AND IsDeleted = 0");
$stmtAuthor->execute([$authorId]);
$author = $stmtAuthor->fetch();

if (!$author) { header('Location: authors.php'); exit; }
$pageTitle = htmlspecialchars($author['Name']) . " - GTSCHUNDER";

// --- PHÂN TRANG ---
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM articles a JOIN articles_authors aa ON a.ArticleID = aa.ArticleID WHERE aa.AuthorID = ? AND a.IsDeleted = 0");
$stmtCount->execute([$authorId]);
$totalArticles = $stmtCount->fetchColumn();
$totalPages = ceil($totalArticles / $limit);
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Lấy danh sách truyện
$sql = '
    SELECT a.*, 
           (SELECT c.`Index` FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterIndex,
           (SELECT c.CreatedAt FROM chapters c WHERE c.ArticleID = a.ArticleID AND c.IsDeleted = 0 ORDER BY c.CreatedAt DESC LIMIT 1) as LatestChapterDate
    FROM articles a
    JOIN articles_authors aa ON a.ArticleID = aa.ArticleID
    WHERE aa.AuthorID = ? AND a.IsDeleted = 0
    GROUP BY a.ArticleID
    ORDER BY a.UpdatedAt DESC
    LIMIT ? OFFSET ?
';
$stmt = $pdo->prepare($sql);
$stmt->execute([$authorId, $limit, $offset]);
$articles = $stmt->fetchAll();
?>

<style>
    /* --- FIX BỐ CỤC CHỐNG ÉP TRANG (QUAN TRỌNG) --- */
    .main-container {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: flex-start;
        width: 100%;
    }
    .content {
        flex: 1 1 73% !important; /* Ép nội dung chiếm 73% */
        min-width: 0 !important;  /* Bắt buộc để chống lỗi rớt chữ */
        width: 100%;
    }
    .sidebar {
        flex: 0 0 25% !important; /* Ép Sidebar chỉ được chiếm 25% */
        min-width: 260px !important;
        max-width: 300px !important;
    }

    .author-info-container {
        background-color: var(--bg-element);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 30px;
        display: flex;
        gap: 30px;
        margin-bottom: 40px;
    }
    .author-info-avatar {
        width: 150px;
        height: 150px;
        flex-shrink: 0;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid var(--border-color);
    }
    .author-info-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .author-info-name { font-size: 24px; color: var(--text-main); margin-bottom: 10px; font-weight: bold; }
    .stat-box { display: inline-block; background: var(--bg-body); padding: 5px 15px; border-radius: 20px; border: 1px solid var(--border-color); margin-bottom: 15px; }
    .stat-number { font-weight: bold; color: var(--primary-theme); font-size: 16px; margin-right: 5px; }
    .author-info-bio h3 { font-size: 16px; margin-bottom: 10px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 5px; }
    .author-info-bio p { color: var(--text-muted); font-size: 13px; line-height: 1.6; }

    /* Phân trang */
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 40px; }
    .pagination-link {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 35px; height: 35px; padding: 0 10px;
        border: 1px solid var(--border-color); border-radius: 4px;
        color: var(--text-muted); font-size: 13px; background: var(--bg-element);
    }
    .pagination-link:hover { border-color: var(--text-main); color: var(--text-main); }
    .pagination-link.active { background: var(--primary-theme); color: #fff; border-color: var(--primary-theme); font-weight: bold; }

    /* Responsive */
    @media (max-width: 991px) { 
        .main-container { flex-direction: column !important; }
        .sidebar { max-width: 100% !important; flex: 1 1 100% !important; margin-top: 30px; }
    }
    @media (max-width: 768px) {
        .author-info-container { flex-direction: column; align-items: center; text-align: center; }
    }
</style>

<div class="main-container">
    <main class="content">
        <section class="section">
            <div class="author-info-container">
                <div class="author-info-avatar">
                    <img src="<?php echo !empty($author['Avatar']) ? htmlspecialchars($author['Avatar']) : BASE_URL.'uploads/avatars/1767718133_695d3cf586583.png'; ?>" alt="Avatar">
                </div>
                <div class="author-info-details">
                    <h1 class="author-info-name"><?php echo htmlspecialchars($author['Name']); ?></h1>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo $totalArticles; ?></span>
                        <span class="stat-label text-muted">Tác Phẩm</span>
                    </div>
                    <?php if (!empty($author['Description'])): ?>
                        <div class="author-info-bio">
                            <h3>Về Tác Giả</h3>
                            <p><?php echo nl2br(htmlspecialchars($author['Description'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section__header">
                <h3>Các Tác Phẩm Của <?php echo htmlspecialchars($author['Name']); ?></h3>
            </div>

            <?php if (count($articles) > 0): ?>
                <div class="card-list">
                    <?php foreach ($articles as $art): ?>
                        <article class="card" onclick="window.location.href='<?= BASE_URL ?>truyen/<?= $art['ArticleID'] ?>'">
                            <div class="card__thumb">
                                <?php if($art['CoverImage']): ?>
                                    <img src="<?= getImageUrl($art['CoverImage']) ?>" alt="<?= htmlspecialchars($art['Title']) ?>">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; background:#333; display:flex; align-items:center; justify-content:center; color:#777;">No Img</div>
                                <?php endif; ?>
                            </div>
                            <h4 class="card__title"><?= htmlspecialchars($art['Title']) ?></h4>
                            
                            <p class="card__chapter">
                                <?php 
                                if (!empty($art['LatestChapterDate'])) {
                                    $chapIdx = !empty($art['LatestChapterIndex']) ? $art['LatestChapterIndex'] : '?';
                                    echo '<i class="fas fa-book-open text-green"></i> Chương ' . $chapIdx . ' - ' . getRelativeTime($art['LatestChapterDate']);
                                } else {
                                    echo '<i class="fas fa-book-open"></i> Chưa có chương';
                                }
                                ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="author-detail.php?id=<?= $authorId ?>&page=<?= $page - 1 ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="author-detail.php?id=<?= $authorId ?>&page=<?= $i ?>" class="pagination-link <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="author-detail.php?id=<?= $authorId ?>&page=<?= $page + 1 ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-muted);">
                    <i class="far fa-folder-open" style="font-size: 40px; margin-bottom: 15px;"></i>
                    <p>Tác giả này chưa có tác phẩm nào</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>
</div>

<?php require_once 'includes/footer.php'; ?>