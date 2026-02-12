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

<link rel="stylesheet" href="<?= BASE_URL ?>css/chapter.css?v=<?= time() ?>">


<div class="viewer-toolbar">
    <div class="toolbar-left">
        <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>" class="btn-back">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        
        <h1 class="chapter-title">  
            <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>" class="btn-home hidden-mobile">
                <i class="fa-solid fa-house me-1"></i>
                <span class="webtoon-name"><?= htmlspecialchars($chapter['ArticleTitle']) ?></span>
            </a>
            
            <span class="divider hidden-mobile">|</span>
            
            <span class="episode-name">
                <span class="hidden-mobile">Chapter</span> <?= $chapter['Index'] ?> 
                <span class="chapter-subtitle"><?= !empty($chapter['Title']) ? ': ' . htmlspecialchars($chapter['Title']) : '' ?></span>
            </span>
        </h1>
    </div>

    <div class="toolbar-right">
        <button id="btn-follow" class="btn-toolbar btn-interest" data-id="<?= $articleId ?>">
            <?php if ($isBookmarked): ?>
                <i class="fa-solid fa-check-circle"></i> <span class="btn-text">Đã theo dõi</span>
            <?php else: ?>
                <i class="fa-solid fa-circle-plus"></i> <span class="btn-text">Theo dõi</span>
            <?php endif; ?>
        </button>

        <span class="divider">|</span>

        <a href="<?= $prevChap ? BASE_URL . 'chapter/' . $articleId . '/' . $prevChap['ChapterID'] : '#' ?>" 
        class="btn-toolbar nav-arrow <?= !$prevChap ? 'disabled' : '' ?>" title="Chương trước">
            <i class="fa-solid fa-caret-left ms-1"></i> <span class="btn-text">Trước</span>
        </a> 

        <span class="divider">|</span>
        
        <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>#chapter-list" class="btn-toolbar" title="Danh sách chương">
            <i class="fa-solid fa-list-ul"></i> <span class="btn-text">DS chương</span>
        </a>
        
        <span class="divider">|</span>
        
        <a href="<?= $nextChap ? BASE_URL . 'chapter/' . $articleId . '/' . $nextChap['ChapterID'] : '#' ?>" 
           class="btn-toolbar nav-arrow <?= !$nextChap ? 'disabled' : '' ?>" title="Chương sau">
            <span class="btn-text">Sau</span> <i class="fa-solid fa-caret-right ms-1"></i>
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
        <div class="viewer-toolbar footer-mode">
            <div class="toolbar-right">
                <button id="btn-follow" class="btn-toolbar btn-interest" data-id="<?= $articleId ?>">
                    <?php if ($isBookmarked): ?>
                        <i class="fa-solid fa-check-circle"></i> <span class="btn-text">Đã theo dõi</span>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-plus"></i> <span class="btn-text">Theo dõi</span>
                    <?php endif; ?>
                </button>

                <a href="<?= $prevChap ? BASE_URL . 'chapter/' . $articleId . '/' . $prevChap['ChapterID'] : '#' ?>" 
                class="btn-toolbar nav-arrow <?= !$prevChap ? 'disabled' : '' ?>" title="Chương trước">
                    <i class="fa-solid fa-caret-left ms-1"></i> <span class="btn-text">Trước</span>
                </a> 

                
                <a href="<?= BASE_URL ?>truyen/<?= $articleId ?>#chapter-list" class="btn-toolbar" title="Danh sách chương">
                    <i class="fa-solid fa-list-ul"></i> <span class="btn-text">DS chương</span>
                </a>
                
                <a href="<?= $nextChap ? BASE_URL . 'chapter/' . $articleId . '/' . $nextChap['ChapterID'] : '#' ?>" 
                class="btn-toolbar nav-arrow <?= !$nextChap ? 'disabled' : '' ?>" title="Chương sau">
                    <span class="btn-text">Sau</span> <i class="fa-solid fa-caret-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="reader-comments-wrapper" id="comment-section">
        <h3><i class="fas fa-comments me-2"></i>Bình luận</h3>
        <?php 
        $id = $articleId; 
        $currentUrl = "../read.php?id=$articleId&chap=$chapterId#comment-section";
        require_once 'includes/comment_section.php'; 
        ?>
    </div>

</main>

<script>
// Hàm xử lý chung cho việc Toggle Follow
function toggleFollow(btn) {
    const articleId = btn.getAttribute('data-id');
    // Lấy tất cả các icon và text trong nút ĐANG ĐƯỢC BẤM
    const icon = btn.querySelector('i');
    const text = btn.querySelector('.btn-text'); // Lưu ý: Cần class .btn-text ở HTML

    // Gọi API (Backend)
    fetch('includes/action_bookmark.php?ajax=1&id=' + articleId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Cập nhật giao diện cho CẢ 2 NÚT (Header và Footer) cùng lúc
            const allButtons = document.querySelectorAll('.btn-interest[data-id="'+articleId+'"]');
            
            allButtons.forEach(button => {
                const i = button.querySelector('i');
                const span = button.querySelector('.btn-text');
                
                if (data.is_bookmarked) {
                    button.classList.add('active'); // Thêm class active nếu cần CSS
                    i.className = 'fa-solid fa-check-circle';
                    if(span) span.innerText = 'Đã theo dõi';
                } else {
                    button.classList.remove('active');
                    i.className = 'fa-solid fa-circle-plus';
                    if(span) span.innerText = 'Theo dõi';
                }
            });
            
        } else if (data.status === 'login_required') {
            alert("Vui lòng đăng nhập để theo dõi truyện!");
            window.location.href = 'login.php';
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Gán sự kiện cho nút trên Header
const btnHeader = document.getElementById('btn-follow');
if(btnHeader) {
    btnHeader.addEventListener('click', function() { toggleFollow(this); });
}

// Gán sự kiện cho nút dưới Footer
const btnFooter = document.getElementById('btn-follow-footer');
if(btnFooter) {
    btnFooter.addEventListener('click', function() { toggleFollow(this); });
}
</script>

</body>
</html>