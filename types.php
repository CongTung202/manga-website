<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// --- CẤU HÌNH PHÂN TRANG ---
$limit = 12; // Số truyện mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 1. Lấy danh sách Categories
$allCats = $pdo->query("SELECT * FROM categories ORDER BY Name ASC")->fetchAll();

// 2. Xử lý Lọc & Đếm tổng
$currentCatId = $_GET['cat_id'] ?? 0;
$pageTitle = "Phân loại truyện";
$baseUrl = "types.php?cat_id=$currentCatId"; // URL cơ sở để tạo link phân trang

if ($currentCatId > 0) {
    foreach($allCats as $c) {
        if ($c['CategoryID'] == $currentCatId) {
            $pageTitle = $c['Name'];
            break;
        }
    }
    // Đếm tổng
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE CategoryID = ? AND IsDeleted = 0");
    $stmtCount->execute([$currentCatId]);
    $totalArticles = $stmtCount->fetchColumn();

    // Lấy dữ liệu (Thêm LIMIT OFFSET)
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE CategoryID = ? AND IsDeleted = 0 ORDER BY UpdatedAt DESC LIMIT $limit OFFSET $offset");
    $stmt->execute([$currentCatId]);
} else {
    $pageTitle = "Tất cả phân loại";
    // Đếm tổng
    $totalArticles = $pdo->query("SELECT COUNT(*) FROM articles WHERE IsDeleted = 0")->fetchColumn();
    
    // Lấy dữ liệu
    $stmt = $pdo->query("SELECT * FROM articles WHERE IsDeleted = 0 ORDER BY UpdatedAt DESC LIMIT $limit OFFSET $offset");
}

$articles = $stmt->fetchAll();
$totalPages = ceil($totalArticles / $limit);

// --- [LOGIC AJAX] ---
if (isset($_GET['ajax'])) {
    renderContent($pageTitle, $articles, $page, $totalPages, $baseUrl);
    exit;
}

require_once 'includes/header.php';
?>

<style>
    /* CSS Tab Navigation */
    .type-nav { 
        display: flex; justify-content: center; gap: 20px; 
        margin-bottom: 40px; border-bottom: 2px solid var(--border-color); 
    }
    .nav-type-item {
        padding: 15px 10px; font-weight: bold; color: var(--text-muted); font-size: 15px;
        border-bottom: 2px solid transparent; margin-bottom: -2px; transition: 0.2s;
        text-decoration: none; text-transform: uppercase; cursor: pointer;
    }
    .nav-type-item:hover { color: var(--text-main); }
    .nav-type-item.active { color: var(--primary-theme); border-bottom-color: var(--primary-theme); }
    
    /* CSS Phân trang */
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 40px; }
    .page-link {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 35px; height: 35px; padding: 0 10px;
        border: 1px solid var(--border-color); border-radius: 4px;
        color: var(--text-muted); text-decoration: none; font-size: 13px;
        transition: 0.2s; background: var(--bg-element);
    }
    .page-link:hover { border-color: var(--text-main); color: var(--text-main); }
    .page-link.active { background: var(--primary-theme); color: #fff; border-color: var(--primary-theme); font-weight: bold; }
    
    .loading-overlay { opacity: 0.5; pointer-events: none; transition: 0.2s; }
</style>

<div class="main-container">
    <main class="content">
        
        <div class="type-nav">
            <a href="types.php" class="nav-type-item ajax-trigger <?= $currentCatId == 0 ? 'active' : '' ?>" data-url="types.php?cat_id=0">
                Tất cả
            </a>
            <?php foreach($allCats as $c): ?>
                <a href="types.php?cat_id=<?= $c['CategoryID'] ?>" 
                   class="nav-type-item ajax-trigger <?= $currentCatId == $c['CategoryID'] ? 'active' : '' ?>" 
                   data-url="types.php?cat_id=<?= $c['CategoryID'] ?>">
                    <?= htmlspecialchars($c['Name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div id="ajax-content">
            <?php renderContent($pageTitle, $articles, $page, $totalPages, $baseUrl); ?>
        </div>

    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contentArea = document.getElementById('ajax-content');

    // Sử dụng Event Delegation để bắt sự kiện cho cả Tab và Pagination (vì Pagination sinh ra sau)
    document.body.addEventListener('click', function(e) {
        // 1. Kiểm tra nếu click vào Tab hoặc Link phân trang
        const target = e.target.closest('.ajax-trigger') || e.target.closest('.page-link');
        
        if (target && !target.classList.contains('active')) {
            e.preventDefault();
            
            // Nếu là Tab thì cập nhật UI active
            if (target.classList.contains('nav-type-item')) {
                document.querySelectorAll('.nav-type-item').forEach(t => t.classList.remove('active'));
                target.classList.add('active');
            }

            const url = target.getAttribute('href') || target.dataset.url;
            if (!url) return;

            // Loading effect
            contentArea.classList.add('loading-overlay');
            
            // Update URL browser
            window.history.pushState(null, '', url);

            // Fetch AJAX
            fetch(url + (url.includes('?') ? '&' : '?') + 'ajax=1')
                .then(res => res.text())
                .then(html => {
                    contentArea.innerHTML = html;
                    contentArea.classList.remove('loading-overlay');
                    // Scroll nhẹ lên đầu list nếu đang ở dưới
                    window.scrollTo({ top: 100, behavior: 'smooth' });
                })
                .catch(err => {
                    console.error(err);
                    contentArea.classList.remove('loading-overlay');
                });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

<?php
// --- HÀM RENDER (Thêm tham số Page & TotalPages) ---
function renderContent($title, $list, $page, $totalPages, $baseUrl) {
?>
    <section class="section">
        <div class="section__header">
            <h3><?= htmlspecialchars($title) ?></h3>
            <span style="font-size: 12px; color: var(--text-muted); margin-left: auto;">
                Trang <?= $page ?> / <?= $totalPages > 0 ? $totalPages : 1 ?>
            </span>
        </div>

        <?php if (count($list) > 0): ?>
            <div class="card-list">
                <?php foreach($list as $art): ?>
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

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= $baseUrl ?>&page=<?= $page - 1 ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>

                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="<?= $baseUrl ?>&page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $baseUrl ?>&page=<?= $page + 1 ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: var(--text-muted);">
                <i class="far fa-folder-open" style="font-size: 40px; margin-bottom: 15px;"></i>
                <p>Chưa có truyện nào thuộc phân loại này.</p>
            </div>
        <?php endif; ?>
    </section>
<?php
}
?>