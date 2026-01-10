<?php
require_once 'includes/db.php';
require_once 'includes/functions.php'; // Gọi file này để dùng getImageUrl

// 1. Lấy ID
$id = $_GET['id'] ?? null;
if (!$id) die("Không tìm thấy truyện.");

// --- [LOGIC MỚI] KIỂM TRA SESSION ĐỂ TRÁNH SPAM VIEW ---
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['viewed_articles'])) {
    $_SESSION['viewed_articles'] = [];
}

if (!in_array($id, $_SESSION['viewed_articles'])) {
    $pdo->prepare("UPDATE articles SET ViewCount = ViewCount + 1 WHERE ArticleID = ?")->execute([$id]);
    $_SESSION['viewed_articles'][] = $id; 
}

// 2. Lấy thông tin
$sql = "SELECT a.*, GROUP_CONCAT(DISTINCT auth.Name SEPARATOR ', ') as Authors, GROUP_CONCAT(DISTINCT g.Name SEPARATOR ', ') as Genres 
        FROM articles a 
        LEFT JOIN articles_authors aa ON a.ArticleID = aa.ArticleID LEFT JOIN authors auth ON aa.AuthorID = auth.AuthorID 
        LEFT JOIN articles_genres ag ON a.ArticleID = ag.ArticleID LEFT JOIN genres g ON ag.GenreID = g.GenreID 
        WHERE a.ArticleID = ? GROUP BY a.ArticleID";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) die("Truyện không tồn tại.");

// 3. Lấy Chapter
$sqlChap = "SELECT * FROM chapters WHERE ArticleID = ? AND IsDeleted = 0 ORDER BY `Index` DESC";
$stmtChap = $pdo->prepare($sqlChap);
$stmtChap->execute([$id]);
$chapters = $stmtChap->fetchAll();

// 4. Check Bookmark
$isBookmarked = false;
if (isset($_SESSION['user_id'])) {
    $stmtCheck = $pdo->prepare("SELECT BookmarkID FROM bookmarks WHERE UserID = ? AND ArticleID = ?");
    $stmtCheck->execute([$_SESSION['user_id'], $id]);
    if ($stmtCheck->rowCount() > 0) $isBookmarked = true;
}

$pageTitle = htmlspecialchars($article['Title']);
require_once 'includes/header.php'; 
?>

<style>
    /* CSS Riêng cho trang Detail (Dark Theme) */
    .detail-info { 
        display: flex; gap: 30px; margin-bottom: 40px; 
        background-color: var(--bg-element); padding: 20px; border-radius: 4px;
        border: 1px solid var(--border-color);
    }
    .detail-thumb { width: 220px; flex-shrink: 0; position: relative; }
    .detail-thumb img { width: 100%; border-radius: 4px; border: 1px solid var(--border-color); }
    
    .detail-meta h1 { font-size: 24px; color: var(--text-main); margin-bottom: 10px; }
    .detail-row { margin-bottom: 10px; font-size: 14px; color: var(--text-muted); }
    .detail-row strong { color: var(--text-main); margin-right: 10px; }
    .detail-desc { margin-top: 20px; line-height: 1.6; color: #bbb; font-size: 14px; }
    
    .btn-action {
        display: inline-block; padding: 10px 25px; border-radius: 4px; font-weight: bold; margin-right: 10px; cursor: pointer; text-align: center;
        transition: 0.2s; border: none; font-size: 14px;
    }
    .btn-read { background: var(--primary-theme); color: #fff; text-decoration: none; }
    .btn-read:hover { opacity: 0.9; color: #fff; }
    
    .btn-follow { background: transparent; border: 1px solid var(--primary-theme); color: var(--primary-theme); }
    .btn-follow:hover { background: var(--primary-theme); color: #fff; }
    
    /* Trạng thái Active (Đã theo dõi) */
    .btn-follow.active { background: #444; border-color: #444; color: #aaa; }
    .btn-follow.active:hover { background: #d63031; border-color: #d63031; color: #fff; } /* Hover vào thì hiện màu đỏ báo hiệu hủy */

    /* Chapter List Style */
    .chapter-list-header { border-bottom: 2px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px; font-weight: bold; font-size: 16px; }
    .chapter-item { 
        display: flex; justify-content: space-between; padding: 12px 10px; 
        border-bottom: 1px solid var(--border-color); transition: 0.2s; text-decoration: none;
    }
    .chapter-item:hover { background-color: var(--bg-hover); }
    .chap-name { color: var(--text-main); font-weight: bold; }
    .chap-date { color: var(--text-muted); font-size: 12px; }
</style>

<div class="main-container">
<main class="content">
    
    <div class="detail-info">
        <div class="detail-thumb">
            <?php if($article['CoverImage']): ?>
                <img src="<?= getImageUrl($article['CoverImage']) ?>" alt="Cover">
            <?php else: ?>
                <div style="width:100%; height:300px; background:#333; display:flex; align-items:center; justify-content:center;">No Image</div>
            <?php endif; ?>
        </div>
        
        <div class="detail-meta">
            <h1><?= htmlspecialchars($article['Title']) ?></h1>
            <div class="detail-row"><strong>Tác giả:</strong> <?= $article['Authors'] ?? 'Đang cập nhật' ?></div>
            <div class="detail-row"><strong>Thể loại:</strong> <?= $article['Genres'] ?? 'Chưa phân loại' ?></div>
            <div class="detail-row">
                <strong>Trạng thái:</strong> 
                <?= $article['Status'] == 1 ? '<span class="text-green">Đang tiến hành</span>' : 'Hoàn thành' ?>
            </div>
            <div class="detail-row"><strong>Lượt xem:</strong> <?= number_format($article['ViewCount']) ?></div>

            <div class="detail-desc">
                <?= nl2br(htmlspecialchars($article['Description'])) ?>
            </div>

            <div style="margin-top: 25px;">
                <?php if(count($chapters) > 0): $firstChap = end($chapters); ?>
                    <a href="read.php?id=<?= $article['ArticleID'] ?>&chap=<?= $firstChap['ChapterID'] ?>" class="btn-action btn-read">
                        <i class="fas fa-book-open"></i> Đọc ngay
                    </a>
                <?php endif; ?>

                <button id="btn-follow-detail" class="btn-action btn-follow <?= $isBookmarked ? 'active' : '' ?>" data-id="<?= $article['ArticleID'] ?>">
                    <?php if ($isBookmarked): ?>
                        <i class="fas fa-check"></i> <span>Đã theo dõi</span>
                    <?php else: ?>
                        <i class="fas fa-heart"></i> <span>Theo dõi</span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="chapter-list-header">Danh sách chương</div>
        <div style="max-height: 500px; overflow-y: auto; padding-right: 5px;">
            <?php if(count($chapters) > 0): ?>
                <?php foreach($chapters as $chap): ?>
                    <a href="read.php?id=<?= $article['ArticleID'] ?>&chap=<?= $chap['ChapterID'] ?>" class="chapter-item">
                        <span class="chap-name">Chapter <?= $chap['Index'] ?> <?= $chap['Title'] ? '- '.htmlspecialchars($chap['Title']) : '' ?></span>
                        <span class="chap-date"><?= date('d/m/Y', strtotime($chap['CreatedAt'])) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="padding: 20px; text-align: center; color: var(--text-muted);">Chưa có chương nào.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once 'includes/comment_section.php'; ?>

</main>

<aside class="sidebar">
    <?php include 'includes/right_sidebar.php'; ?>
</aside>

<script>
document.getElementById('btn-follow-detail').addEventListener('click', function() {
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
                // Đã theo dõi -> Đổi sang style Active
                btn.classList.add('active');
                icon.className = 'fas fa-check';
                text.innerText = 'Đã theo dõi';
            } else {
                // Hủy theo dõi -> Đổi về style thường
                btn.classList.remove('active');
                icon.className = 'fas fa-heart';
                text.innerText = 'Theo dõi';
            }
        } else if (data.status === 'login_required') {
            if(confirm("Bạn cần đăng nhập để theo dõi truyện. Đăng nhập ngay?")) {
                window.location.href = 'login.php';
            }
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>

<?php require_once 'includes/footer.php'; ?>