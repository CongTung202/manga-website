<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 1. Lấy ID
$id = $_GET['id'] ?? null;
$sortParam = $_GET['sort'] ?? 'desc'; // Mặc định là mới nhất
$sortOrder = ($sortParam === 'asc') ? 'ASC' : 'DESC';
// [HÀM MỚI] Giúp tạo link giữ nguyên các tham số khác (như id, slug...) và chỉ thay đổi sort
function makeSortUrl($sortValue) {
    $params = $_GET; // Lấy tất cả tham số hiện tại trên URL
    if($sortValue !== null) {
        $params['sort'] = $sortValue; // Gán tham số sort mới (nếu không null)
    }
    return '?' . http_build_query($params); // Trả về chuỗi query (ví dụ: ?id=10&sort=asc)
}

// ... Code query database ...
// Query SQL
$sqlChap = "SELECT c.*, 
            (SELECT ImageURL FROM chapter_images ci 
             WHERE ci.ChapterID = c.ChapterID 
             ORDER BY ci.ImageID ASC LIMIT 1) as ChapterThumb 
            FROM chapters c 
            WHERE c.ArticleID = ? AND c.IsDeleted = 0 
            ORDER BY c.`Index` $sortOrder"; // Nhớ biến $sortOrder ở đây

$stmtChap = $pdo->prepare($sqlChap);
$stmtChap->execute([$id]);
$chapters = $stmtChap->fetchAll();

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

// 3. Lấy Chapter (CÓ SẮP XẾP & PHÂN TRANG)
// Lấy tham số sort từ URL, mặc định là 'desc' (Mới nhất)
$sortParam = $_GET['sort'] ?? 'desc';
$sortOrder = ($sortParam === 'asc') ? 'ASC' : 'DESC';

// Phân trang chapter
$ITEMS_PER_PAGE = 10; // Số chapter hiển thị trên mỗi trang
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Lấy tổng số chapter
$sqlTotalChap = "SELECT COUNT(*) as total FROM chapters WHERE ArticleID = ? AND IsDeleted = 0";
$stmtTotal = $pdo->prepare($sqlTotalChap);
$stmtTotal->execute([$id]);
$totalChapters = $stmtTotal->fetch()['total'];
$totalPages = ceil($totalChapters / $ITEMS_PER_PAGE);

// Đảm bảo currentPage không vượt quá totalPages
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $ITEMS_PER_PAGE;

// Query SQL đã sửa để nhận biến $sortOrder và LIMIT
$sqlChap = "SELECT c.*, 
            (SELECT ImageURL FROM chapter_images ci 
             WHERE ci.ChapterID = c.ChapterID 
             ORDER BY ci.ImageID ASC LIMIT 1) as ChapterThumb 
            FROM chapters c 
            WHERE c.ArticleID = ? AND c.IsDeleted = 0 
            ORDER BY c.`Index` $sortOrder
            LIMIT ? OFFSET ?"; // <-- Thêm LIMIT và OFFSET

$stmtChap = $pdo->prepare($sqlChap);
$stmtChap->execute([$id, $ITEMS_PER_PAGE, $offset]);
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

<script>
    // Lưu BASE_URL để dùng trong JavaScript
    const BASE_URL = '<?= BASE_URL ?>';
</script>

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
                    <span>Tác giả: </span> <span><?= $article['Authors'] ?? 'Tác giả đang cập nhật' ?></span>
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
                        <span class="count"><?= number_format($article['ViewCount']) ?></span>
                    </button>
                    
                    <!-- NÚT COPY LINK -->
                    <button id="btn-copy-link" class="btn-copy-link" title="Copy link truyện">
                        <i class="fas fa-link"></i> Copy link
                    </button>
                    
                    <!-- NÚT SHARE -->
                    <button class="btn-naver-white share-btn">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="divider"></div>

<section class="chapter-section">
    
    <div class="list-header-row">
        <div class="list-total">
            Tổng <?= $totalChapters ?> tập
        </div>
        <div class="list-sort">
            <a href="<?= makeSortUrl('desc') ?>" class="<?= $sortParam === 'desc' ? 'active' : '' ?>">
                Xem mới nhất
            </a>
            
            <span class="sep">|</span>
            
            <a href="<?= makeSortUrl('asc') ?>" class="<?= $sortParam === 'asc' ? 'active' : '' ?>">
                Xem từ đầu
            </a>
        </div>
    </div>
    
            <div class="chapter-list">
                <?php if(count($chapters) > 0): ?>
                    <?php foreach($chapters as $chap): ?>
                        <?php 
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

            <!-- PHÂN TRANG CHAPTER -->
            <?php if($totalPages > 1): ?>
            <div class="chapter-pagination">
                <!-- NÚT PREVIOUS -->
                <?php if($currentPage > 1): ?>
                    <a href="<?= makeSortUrl(null) . '&page=1' ?>" class="prev" title="Trang đầu">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a href="<?= makeSortUrl(null) . '&page=' . ($currentPage - 1) ?>" class="prev" title="Trang trước">
                        Trước
                    </a>
                <?php else: ?>
                    <span class="prev disabled">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                    <span class="prev disabled">Trước</span>
                <?php endif; ?>

                <!-- CÁC TRANG SỐ -->
                <?php 
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if($startPage > 1): ?>
                    <span>...</span>
                <?php endif;
                
                for($p = $startPage; $p <= $endPage; $p++):
                    if($p === $currentPage): ?>
                        <span class="active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= makeSortUrl(null) . '&page=' . $p ?>"><?= $p ?></a>
                    <?php endif;
                endfor;
                
                if($endPage < $totalPages): ?>
                    <span>...</span>
                <?php endif; ?>

                <!-- NÚT NEXT -->
                <?php if($currentPage < $totalPages): ?>
                    <a href="<?= makeSortUrl(null) . '&page=' . ($currentPage + 1) ?>" class="next" title="Trang sau">
                        Sau
                    </a>
                    <a href="<?= makeSortUrl(null) . '&page=' . $totalPages ?>" class="next" title="Trang cuối">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="next disabled">Sau</span>
                    <span class="next disabled">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

        <?php require_once 'includes/comment_section.php'; ?>

    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>
</div>

<script>
// ========================================
// TOAST NOTIFICATION SYSTEM
// ========================================
function showToast(message, type = 'info', duration = 3000) {
    console.log('showToast được gọi:', message, type);
    
    // Tạo container nếu chưa tồn tại
    let container = document.getElementById('toast-container');
    if (!container) {
        console.log('Tạo toast-container mới');
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Tạo toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Icon dựa trên type
    let iconClass = 'fas fa-info-circle';
    if (type === 'success') iconClass = 'fas fa-check-circle';
    if (type === 'error') iconClass = 'fas fa-exclamation-circle';
    if (type === 'warning') iconClass = 'fas fa-exclamation-triangle';
    
    toast.innerHTML = `
        <i class="${iconClass}"></i>
        <span class="toast-message">${message}</span>
        <span class="toast-close">&times;</span>
    `;

    container.appendChild(toast);
    console.log('Toast thêm vào container');

    // Xử lý nút đóng
    toast.querySelector('.toast-close').addEventListener('click', function() {
        removeToast(toast);
    });

    // Auto remove sau duration
    const timeoutId = setTimeout(() => {
        removeToast(toast);
    }, duration);

    // Lưu timeoutId để có thể hủy nếu click close
    toast.timeoutId = timeoutId;

    return toast;
}

function removeToast(toast) {
    clearTimeout(toast.timeoutId);
    toast.classList.add('removing');
    setTimeout(() => {
        toast.remove();
    }, 300);
}

// ========================================
// NÚT QUAN TÂM - BOOKMARK
// ========================================
console.log('Đang tìm kiếm nút quan tâm...');
const btnFollowDetail = document.getElementById('btn-follow-detail');
console.log('Nút quan tâm:', btnFollowDetail);

if(btnFollowDetail) {
    console.log('Nút quan tâm được tìm thấy, gắn event listener...');
    btnFollowDetail.addEventListener('click', function() {
        console.log('Nút quan tâm được click!');
        const btn = this;
        const articleId = btn.getAttribute('data-id');
        console.log('Article ID:', articleId);
        const icon = btn.querySelector('i');
        const text = btn.querySelector('span:nth-child(2)'); // Chọn span text

        // Hiển thị loading state
        btn.disabled = true;
        const originalText = text.innerText;
        text.innerText = 'Đang xử lý...';

        fetch(BASE_URL + 'includes/action_bookmark.php?ajax=1&id=' + articleId)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error, status = ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.status === 'success') {
                if (data.is_bookmarked) {
                    btn.classList.add('active');
                    icon.className = 'fas fa-check';
                    text.innerText = 'Đã quan tâm';
                    showToast('Đã thêm vào quan tâm', 'success');
                } else {
                    btn.classList.remove('active');
                    icon.className = 'fas fa-plus';
                    text.innerText = 'Quan tâm';
                    showToast('Đã bỏ quan tâm', 'info');
                }
            } else if (data.status === 'login_required') {
                showToast('Bạn cần đăng nhập để theo dõi truyện', 'warning', 4000);
                // Auto redirect sau 2 giây
                setTimeout(() => {
                    window.location.href = BASE_URL + 'login.php';
                }, 2000);
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Lỗi kết nối: ' + error.message, 'error');
            text.innerText = originalText;
        })
        .finally(() => {
            btn.disabled = false;
        });
    });
} else {
    console.error('KHÔNG TÌM THẤY NÚT QUAN TÂM! ID: btn-follow-detail');
}

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

// ========================================
// CHỨC NĂNG COPY LINK
// ========================================
const btnCopyLink = document.getElementById('btn-copy-link');
if(btnCopyLink) {
    btnCopyLink.addEventListener('click', function() {
        const currentUrl = window.location.href;
        
        // Copy vào clipboard
        navigator.clipboard.writeText(currentUrl).then(function() {
            // Hiển thị tooltip "Copied!"
            btnCopyLink.classList.add('copied');
            showToast('Copied', 'success', 2000);
            
            // Bỏ class sau 2 giây
            setTimeout(function() {
                btnCopyLink.classList.remove('copied');
            }, 2000);
        }).catch(function(err) {
            showToast('Không thể sao chép link', 'error');
        });
    });
}

// ========================================
// CHỨC NĂNG ĐỌC MỚI NHẤT
// ========================================
const btnReadLatest = document.getElementById('btn-read-latest');
if(btnReadLatest) {
    btnReadLatest.addEventListener('click', function() {
        // Lấy URL hiện tại và thêm tham số sort=desc để xem mới nhất
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('sort', 'desc'); // Xem mới nhất
        currentUrl.searchParams.delete('page'); // Xóa tham số page để về trang 1
        
        // Tìm link chương mới nhất (chương đầu tiên trong danh sách)
        const firstChapterLink = document.querySelector('.chapter-list .chapter-row');
        if(firstChapterLink) {
            window.location.href = firstChapterLink.href;
        } else {
            showToast('Không tìm thấy chương nào để đọc', 'warning');
        }
    });
}

// ========================================
// CHỨC NĂNG ĐỌC TỪ ĐẦU
// ========================================
const btnReadFirst = document.getElementById('btn-read-first');
if(btnReadFirst) {
    btnReadFirst.addEventListener('click', function() {
        // Lấy URL hiện tại và thêm tham số sort=asc để xem từ đầu
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('sort', 'asc'); // Xem từ đầu
        currentUrl.searchParams.delete('page'); // Xóa tham số page để về trang 1
        
        // Tìm link chương đầu tiên (chương đầu tiên trong danh sách)
        const firstChapterLink = document.querySelector('.chapter-list .chapter-row');
        if(firstChapterLink) {
            window.location.href = firstChapterLink.href;
        } else {
            showToast('Không tìm thấy chương nào để đọc', 'warning');
        }
    });
}

</script>
