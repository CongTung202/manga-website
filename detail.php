<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 1. Lấy ID
$id = $_GET['id'] ?? null;
if (!$id) die("Không tìm thấy truyện.");

// --- [LOGIC] KIỂM TRA SESSION ĐỂ TRÁNH SPAM VIEW ---
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

// 3. Lấy Chapter (ĐÃ SỬA)
// Lưu ý: Bạn cần thay 'chapter_images' và 'ImageURL' đúng với tên bảng/cột trong database của bạn
$sqlChap = "SELECT c.*, 
            (SELECT ImageURL FROM chapter_images ci 
             WHERE ci.ChapterID = c.ChapterID 
             ORDER BY ci.ImageID ASC LIMIT 1) as ChapterThumb 
            FROM chapters c 
            WHERE c.ArticleID = ? AND c.IsDeleted = 0 
            ORDER BY c.`Index` DESC";

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

<link rel="stylesheet" href="<?= BASE_URL ?>css/detail.css">

<div class="main-container">
    <main class="content">
        
        <div class="detail-header">
            <div class="detail-thumb" id="cover-trigger">
                <?php if($article['CoverImage']): ?>
                    <img src="<?= getImageUrl($article['CoverImage']) ?>" alt="Cover" id="thumb-img">
                    
                    <div class="zoom-icon-corner">
                        <i class="fas fa-search-plus"></i>
                    </div>
                <?php else: ?>
                    <div class="no-image">No Image</div>
                <?php endif; ?>
            </div>

            <div id="image-modal" class="modal-overlay">
                <span class="close-modal">&times;</span>
                <img class="modal-content" id="full-image">
                <div id="caption"></div>
            </div>
            
            <div class="detail-info-right">
                <h1 class="story-title"><?= htmlspecialchars($article['Title']) ?></h1>
                
                <div class="meta-line author">
                    <span><?= $article['Authors'] ?? 'Tác giả đang cập nhật' ?></span>
                </div>

                <div class="story-desc">
                    <?= nl2br(htmlspecialchars($article['Description'])) ?>
                </div>

                <div class="meta-tags">
                    <?php 
                    if (!empty($article['Genres'])) {
                        $tags = explode(', ', $article['Genres']);
                        foreach($tags as $tag) {
                            echo '<span class="tag-item">#' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                    }
                    ?>
                </div>

                <div class="action-bar">
                    <button id="btn-follow-detail" class="btn-naver-green <?= $isBookmarked ? 'active' : '' ?>" data-id="<?= $article['ArticleID'] ?>">
                        <?php if ($isBookmarked): ?>
                            <i class="fas fa-check"></i> <span>Đã quan tâm</span>
                        <?php else: ?>
                            <i class="fas fa-plus"></i> <span>Quan tâm</span>
                        <?php endif; ?>
                        <span class="count"><?= number_format($article['ViewCount']) ?></span> </button>

                    <?php if(count($chapters) > 0): $firstChap = end($chapters); ?>
                        <a href="<?= BASE_URL ?>chapter/<?= $article['ArticleID'] ?>/<?= $firstChap['ChapterID'] ?>" class="btn-naver-white">
                            Đọc tập 1
                        </a>
                    <?php endif; ?>
                    
                    <button class="btn-naver-white share-btn">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <section class="chapter-section">
            <div class="section-title">Tổng <?= count($chapters) ?> tập</div>
            
        <div class="chapter-list">
            <?php if(count($chapters) > 0): ?>
                <?php foreach($chapters as $chap): ?>
                    <?php 
                        // Xử lý logic chọn ảnh: Ưu tiên ảnh chương, nếu không có thì lấy ảnh bìa
                        $thumbSrc = !empty($chap['ChapterThumb']) 
                                    ? getImageUrl($chap['ChapterThumb']) 
                                    : getImageUrl($article['CoverImage']);
                    ?>
                    <a href="<?= BASE_URL ?>chapter/<?= $id ?>/<?= $chap['ChapterID'] ?>" class="chapter-row">
                        <div class="chap-thumb-img">
                            <img src="<?= $thumbSrc ?>" alt="Chapter Thumb" loading="lazy">
                        </div>

                        <div class="chap-info">
                            <span class="chap-title">
                                Chapter <?= $chap['Index'] ?> <?= $chap['Title'] ? ': '.htmlspecialchars($chap['Title']) : '' ?>
                            </span>
                            <div class="chap-meta">
                                <span class="chap-date"><?= date('y.m.d', strtotime($chap['CreatedAt'])) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-chap">Chưa có chương nào.</p>
            <?php endif; ?>
        </div>
        </section>

        <?php require_once 'includes/comment_section.php'; ?>

    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>
</div>

<script>
document.getElementById('btn-follow-detail').addEventListener('click', function() {
    const btn = this;
    const articleId = btn.getAttribute('data-id');
    const icon = btn.querySelector('i');
    const text = btn.querySelector('span:nth-child(2)'); // Chọn span text

    fetch('includes/action_bookmark.php?ajax=1&id=' + articleId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.is_bookmarked) {
                btn.classList.add('active');
                icon.className = 'fas fa-check';
                text.innerText = 'Đã quan tâm';
            } else {
                btn.classList.remove('active');
                icon.className = 'fas fa-plus';
                text.innerText = 'Quan tâm';
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

const modal = document.getElementById("image-modal");
const thumbTrigger = document.getElementById("cover-trigger");
const modalImg = document.getElementById("full-image");
const thumbImg = document.getElementById("thumb-img");
const spanClose = document.getElementsByClassName("close-modal")[0];

// Khi click vào thumbnail -> Mở Modal
if (thumbTrigger && thumbImg) {
    thumbTrigger.onclick = function() {
        modal.style.display = "flex"; // Dùng flex để căn giữa
        modalImg.src = thumbImg.src;  // Lấy ảnh từ thumbnail gán vào ảnh to
    }
}

// Khi click vào nút X -> Đóng
spanClose.onclick = function() {
    modal.style.display = "none";
}

// Khi click ra vùng ngoài ảnh (vùng đen) -> Đóng
modal.onclick = function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
}

// Bấm phím ESC cũng đóng
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape" && modal.style.display === "flex") {
        modal.style.display = "none";
    }
});

</script>
