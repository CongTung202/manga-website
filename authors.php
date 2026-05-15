<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Tác Giả - GTSCHUNDER";
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

// --- CẤU HÌNH PHÂN TRANG ---
$limit = 12; // Số tác giả mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Lấy tổng số tác giả
$stmtTotalCount = $pdo->query("SELECT COUNT(*) FROM authors WHERE IsDeleted = 0");
$totalAuthors = $stmtTotalCount->fetchColumn();
$totalPages = ceil($totalAuthors / $limit);

if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Lấy danh sách tác giả
$sql = "
    SELECT a.AuthorID, a.Name, a.Avatar, a.Description,
           COUNT(DISTINCT aa.ArticleID) as TotalArticles
    FROM authors a
    LEFT JOIN articles_authors aa ON a.AuthorID = aa.AuthorID
    LEFT JOIN articles art ON aa.ArticleID = art.ArticleID AND art.IsDeleted = 0
    WHERE a.IsDeleted = 0
    GROUP BY a.AuthorID, a.Name, a.Avatar, a.Description
    ORDER BY a.Name ASC
    LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$limit, $offset]);
$authors = $stmt->fetchAll();
?>

<style>
    /* --- FIX BỐ CỤC CHỐNG ÉP TRANG --- */
    .main-container {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: flex-start;
        width: 100%;
    }
    .content {
        flex: 1 1 73% !important;
        min-width: 0 !important; 
        width: 100%;
    }
    .sidebar {
        flex: 0 0 25% !important;
        min-width: 260px !important;
        max-width: 300px !important;
    }

    /* --- LƯỚI & THIẾT KẾ CARD MỚI --- */
    .authors-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    
    .author-card {
        background-color: var(--bg-element);
        border: 1px solid var(--border-color);
        border-radius: 12px; /* Bo góc mượt hơn */
        overflow: hidden; /* Cắt phần background cover tràn ra ngoài */
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); /* Shadow nhẹ mặc định */
        display: flex;
        flex-direction: column;
    }
    
    .author-card:hover { 
        transform: translateY(-5px); 
        border-color: var(--primary-theme); 
        box-shadow: 0 10px 20px rgba(0,0,0,0.2); /* Shadow nổi lên khi hover */
    }

    /* Dải cover background (Nếu không có ảnh cover thì dùng gradient) */
    .author-cover {
        height: 70px;
        background: linear-gradient(135deg, var(--primary-theme) 0%, var(--accent-color) 100%);
        width: 100%;
    }

    /* Avatar vắt ngang cover */
    .author-avatar {
        width: 80px;
        height: 80px;
        margin: -40px auto 10px; /* Đẩy giật lùi lên trên 50% */
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid var(--bg-element); /* Viền tiệp màu nền tạo cảm giác cắt lõm */
        background-color: var(--bg-element);
        position: relative;
        z-index: 2;
    }
    .author-avatar img { width: 100%; height: 100%; object-fit: cover; }
    
    .author-card-body {
        padding: 0 15px 20px;
        display: flex;
        flex-direction: column;
        flex: 1; /* Để các card có độ dài bằng nhau */
    }

    .author-name { 
        font-size: 16px; 
        margin-bottom: 5px; 
        line-height: 1.3;
    }
    .author-name a { color: var(--text-main); font-weight: 800; }
    .author-name a:hover { color: var(--primary-theme); }

    /* Stats Badge */
    .author-stats { 
        font-size: 12px; 
        color: var(--text-main); 
        margin-bottom: 12px; 
        font-weight: 600; 
        background: rgba(255,255,255,0.03); /* Nền siêu nhạt */
        padding: 6px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        display: inline-block;
        margin-left: auto;
        margin-right: auto;
    }
    .author-stats i { color: var(--primary-theme); margin-right: 4px; }

    /* Mô tả giới hạn 3 dòng */
    .author-description { 
        font-size: 12px; 
        color: var(--text-muted); 
        margin-bottom: 15px; 
        line-height: 1.5;
        flex: 1; /* Tự đẩy nút bấm xuống dưới cùng */
        
        display: -webkit-box;
        -webkit-line-clamp: 3; /* Cắt chữ sau 3 dòng */
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Nút bấm hiện đại - Đã sửa lỗi trùng màu */
    .author-view-btn {
        display: block;
        width: 100%;
        padding: 10px;
        background-color: var(--primary-theme); /* Dùng màu chủ đạo làm nền đặc */
        color: #ffffff !important; /* Ép chữ màu trắng sáng tuyệt đối */
        border-radius: 6px;
        font-size: 13px; /* Tăng cỡ chữ lên 1 chút cho dễ đọc */
        font-weight: bold;
        transition: all 0.2s ease;
        border: none;
        text-transform: uppercase; /* Viết hoa cho mạnh mẽ (Tùy chọn, bạn có thể xóa dòng này nếu không thích) */
        letter-spacing: 0.5px;
    }
    
    .author-view-btn:hover { 
        background-color: var(--accent-color); /* Đổi sang màu sáng hơn khi di chuột */
        color: #ffffff !important; 
        transform: translateY(-2px); /* Hiệu ứng nảy lên nhẹ */
        box-shadow: 0 4px 8px rgba(0,0,0,0.3); /* Đổ bóng khi hover */
    }
    
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
        .authors-grid { grid-template-columns: repeat(3, 1fr); } 
    }
    @media (max-width: 768px) { .authors-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px) { .authors-grid { grid-template-columns: 1fr; } }
</style>

<div class="main-container">
    <main class="content">
        <section class="section">
            <div class="section__header" style="flex-direction: column; align-items: flex-start; gap: 5px;">
                <h3 style="white-space: nowrap;">Danh Sách Tác Giả</h3>
                <span class="section__filters">Khám phá các tác giả nổi tiếng và những tác phẩm của họ</span>
            </div>
        </section>

        <section class="section">
            <?php if (count($authors) > 0): ?>
                <div class="authors-grid">
                    <?php foreach ($authors as $author): ?>
                        <div class="author-card">
                            <div class="author-cover"></div>
                            
                            <div class="author-avatar">
                                <img src="<?php echo !empty($author['Avatar']) ? htmlspecialchars($author['Avatar']) : BASE_URL.'uploads/avatars/1767718133_695d3cf586583.png'; ?>" alt="Avatar">
                            </div>
                            
                            <div class="author-card-body">
                                <h3 class="author-name">
                                    <a href="<?php echo BASE_URL; ?>author/<?php echo $author['AuthorID']; ?>">
                                        <?php echo htmlspecialchars($author['Name']); ?>
                                    </a>
                                </h3>
                                
                                <div class="author-stats">
                                    <i class="fas fa-book-open"></i> <?php echo $author['TotalArticles']; ?> Tác phẩm
                                </div>
                                
                                <p class="author-description" title="<?php echo htmlspecialchars($author['Description']); ?>">
                                    <?php echo !empty($author['Description']) ? htmlspecialchars($author['Description']) : 'Tác giả này chưa có mô tả...'; ?>
                                </p>
                                
                                <a href="<?php echo BASE_URL; ?>author/<?php echo $author['AuthorID']; ?>" class="author-view-btn">
                                    <i class="fas fa-eye me-1"></i> Xem Chi Tiết
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="authors.php?page=<?php echo $page - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="authors.php?page=<?php echo $i; ?>" class="pagination-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="authors.php?page=<?php echo $page + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-muted);">
                    <i class="fas fa-users" style="font-size: 40px; opacity: 0.5; margin-bottom: 15px;"></i>
                    <p>Không có tác giả nào trong hệ thống.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>
</div>

<?php require_once 'includes/footer.php'; ?>